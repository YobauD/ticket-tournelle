<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * Journal d'audit : trace des actions sensibles (contrainte C5).
 * Les lignes ne sont jamais modifiées ni supprimées par l'application.
 */
final class Log
{
    /**
     * @param string      $action  Code de l'action : login_success, user_created, role_changed...
     * @param string|null $target  Objet concerné : 'user:12', 'ticket:48'
     * @param string|null $details Précision lisible (jamais de mot de passe ni de donnée superflue)
     * @param int|null    $userId  Auteur ; par défaut l'utilisateur connecté
     */
    public static function write(string $action, ?string $target = null, ?string $details = null, ?int $userId = null): void
    {
        Database::query(
            'INSERT INTO logs (user_id, action, target, details, ip_address) VALUES (?, ?, ?, ?, ?)',
            [
                $userId ?? Auth::id(),
                $action,
                $target,
                $details !== null ? mb_substr($details, 0, 500) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]
        );
    }

    // ------------------------------------------------------------------
    //  Consultation (écran d'administration, lecture seule)
    // ------------------------------------------------------------------

    /** Actions de sécurité, mises en évidence dans l'écran du journal. */
    public const SECURITY_ACTIONS = ['login_failed', 'login_blocked', 'login_throttled', 'account_locked', 'password_request_unknown'];

    /**
     * Lignes du journal correspondant aux filtres, les plus récentes en premier.
     * @param array $filters action, q (recherche libre), from et to (dates AAAA-MM-JJ déjà validées)
     */
    public static function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::conditions($filters);
        $params[] = $limit;
        $params[] = $offset;

        return Database::query(
            'SELECT l.*, u.first_name, u.last_name, u.email
               FROM logs l
               LEFT JOIN users u ON u.id = l.user_id' . $where . '
              ORDER BY l.id DESC
              LIMIT ? OFFSET ?',
            $params
        )->fetchAll();
    }

    public static function count(array $filters): int
    {
        [$where, $params] = self::conditions($filters);

        return (int) Database::query(
            'SELECT COUNT(*) FROM logs l LEFT JOIN users u ON u.id = l.user_id' . $where,
            $params
        )->fetchColumn();
    }

    /** Codes d'action présents dans le journal, pour le menu de filtre. */
    public static function actions(): array
    {
        return Database::query('SELECT DISTINCT action FROM logs ORDER BY action')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Libellés lisibles des cibles « user:12 », « category:3 », « location:5 », « job_title:2 ».
     * Une requête par type d'objet pour toute la page, au lieu d'une par ligne.
     *
     * @return array<string, string> cible => libellé
     */
    public static function targetLabels(array $rows): array
    {
        $ids = ['user' => [], 'category' => [], 'location' => [], 'job_title' => []];
        foreach ($rows as $row) {
            if (preg_match('/^(user|category|location|job_title):(\d+)$/', (string) $row['target'], $m)) {
                $ids[$m[1]][(int) $m[2]] = (int) $m[2];
            }
        }

        $sql = [
            'user'     => "SELECT id, CONCAT(first_name, ' ', UPPER(last_name)) FROM users WHERE id IN (%s)",
            'category' => 'SELECT id, label_fr FROM categories WHERE id IN (%s)',
            'location' => 'SELECT id, name FROM locations WHERE id IN (%s)',
            'job_title' => 'SELECT id, label_fr FROM job_titles WHERE id IN (%s)',
        ];

        $labels = [];
        foreach ($ids as $type => $list) {
            if ($list === []) {
                continue;
            }
            // Autant de marqueurs « ? » que d'identifiants : les valeurs restent liées
            $placeholders = implode(', ', array_fill(0, count($list), '?'));
            $found = Database::query(sprintf($sql[$type], $placeholders), array_values($list))->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($found as $id => $label) {
                $labels[$type . ':' . $id] = $label;
            }
        }
        return $labels;
    }

    /** Clause WHERE commune à search() et count(). */
    private static function conditions(array $filters): array
    {
        $where  = [];
        $params = [];

        if (($filters['action'] ?? '') !== '') {
            $where[]  = 'l.action = ?';
            $params[] = $filters['action'];
        }
        if (($filters['q'] ?? '') !== '') {
            // % et _ saisis sont cherchés tels quels, pas interprétés comme jokers
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(u.email LIKE ? OR u.last_name LIKE ? OR l.target LIKE ? OR l.details LIKE ? OR l.ip_address LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (($filters['from'] ?? '') !== '') {
            $where[]  = 'l.created_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (($filters['to'] ?? '') !== '') {
            $where[]  = 'l.created_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $filters['to'];
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
