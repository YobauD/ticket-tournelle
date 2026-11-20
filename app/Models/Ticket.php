<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DomainException;

/**
 * Tickets : lecture, création et règles de gestion du cycle de vie.
 */
final class Ticket
{
    /**
     * Transitions autorisées : statut actuel => statuts atteignables.
     *
     *   Nouveau ──► En cours ◄──► En attente
     *      │           │  ▲            │
     *      ▼           ▼  │(réouverture)
     *   Rejeté       Résolu ◄──────────┘
     *                  │
     *                  ▼
     *               Clôturé
     */
    public const TRANSITIONS = [
        'new'         => ['in_progress', 'rejected'],
        'in_progress' => ['pending', 'resolved', 'rejected'],
        'pending'     => ['in_progress', 'resolved'],
        'resolved'    => ['closed', 'in_progress'],
        'closed'      => [],
        'rejected'    => [],
    ];

    /** Statuts pour lesquels le technicien doit expliquer sa décision au déclarant. */
    public const NOTE_REQUIRED = ['pending', 'resolved', 'rejected'];

    private const OPEN = "('new', 'in_progress', 'pending')";

    /** Sélection commune : le ticket et tous ses libellés, dans les deux langues. */
    private const SELECT = '
        SELECT t.*,
               s.code AS status_code, s.label_fr AS status_fr, s.label_en AS status_en, s.is_final,
               p.code AS priority_code, p.label_fr AS priority_fr, p.label_en AS priority_en,
               p.sla_response_h, p.sla_resolution_h,
               c.label_fr AS category_fr, c.label_en AS category_en,
               l.name AS location_name,
               u.first_name AS user_first_name, u.last_name AS user_last_name,
               u.email AS user_email, u.lang AS user_lang,
               ujt.label_fr AS user_title_fr, ujt.label_en AS user_title_en,
               tech.first_name AS tech_first_name, tech.last_name AS tech_last_name,
               tech.email AS tech_email, tech.lang AS tech_lang,
               DATE_ADD(t.created_at, INTERVAL p.sla_resolution_h HOUR) AS due_at,
               (s.code IN ' . self::OPEN . '
                AND NOW() > DATE_ADD(t.created_at, INTERVAL p.sla_resolution_h HOUR)) AS is_overdue
          FROM tickets t
          JOIN status     s ON s.id = t.status_id
          JOIN priorities p ON p.id = t.priority_id
          JOIN categories c ON c.id = t.category_id
          JOIN locations  l ON l.id = t.location_id
          JOIN users      u ON u.id = t.user_id
          LEFT JOIN job_titles ujt ON ujt.id = u.job_title_id
          LEFT JOIN users tech ON tech.id = t.technician_id';

    // ------------------------------------------------------------------
    //  Lecture
    // ------------------------------------------------------------------

    public static function find(int $id): ?array
    {
        return Database::query(self::SELECT . ' WHERE t.id = ?', [$id])->fetch() ?: null;
    }

    /**
     * Tickets d'un déclarant.
     * @param string $scope all | active (en cours de traitement) | done (clôturés ou rejetés)
     */
    public static function forUser(int $userId, string $scope = 'all'): array
    {
        $sql = self::SELECT . ' WHERE t.user_id = ?';
        if ($scope === 'active') {
            $sql .= ' AND s.is_final = 0';
        } elseif ($scope === 'done') {
            $sql .= ' AND s.is_final = 1';
        }
        return Database::query($sql . ' ORDER BY t.created_at DESC, t.id DESC', [$userId])->fetchAll();
    }

    /** Nombre de tickets d'un déclarant par code de statut : ['new' => 2, 'pending' => 1, ...] */
    public static function countsForUser(int $userId): array
    {
        return Database::query(
            'SELECT s.code, COUNT(*) FROM tickets t JOIN status s ON s.id = t.status_id
              WHERE t.user_id = ? GROUP BY s.code',
            [$userId]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * File d'attente des techniciens, la plus urgente en premier, une page à la fois.
     *
     * @param array $filters status (open|all|code), priority (code), location (texte cherché dans le nom du lieu),
     *                       level (1-3), scope (all|mine|unassigned)
     */
    public static function queue(array $filters, int $technicianId, int $limit, int $offset): array
    {
        [$where, $params] = self::queueConditions($filters, $technicianId);
        $params[] = $limit;
        $params[] = $offset;

        return Database::query(
            self::SELECT . $where . ' ORDER BY p.sort_order DESC, t.created_at ASC, t.id ASC LIMIT ? OFFSET ?',
            $params
        )->fetchAll();
    }

    /** Nombre total de tickets correspondant aux filtres (pour la pagination). */
    public static function countQueue(array $filters, int $technicianId): int
    {
        [$where, $params] = self::queueConditions($filters, $technicianId);

        return (int) Database::query(
            'SELECT COUNT(*) FROM tickets t
               JOIN status s     ON s.id = t.status_id
               JOIN priorities p ON p.id = t.priority_id
               JOIN locations l  ON l.id = t.location_id' . $where,
            $params
        )->fetchColumn();
    }

    /**
     * Clause WHERE commune à queue() et countQueue().
     * Chaque filtre ajoute une condition et sa valeur liée : aucune saisie
     * n'est concaténée dans la requête.
     *
     * @return array{0: string, 1: array} [clause WHERE (ou chaîne vide), valeurs liées]
     */
    private static function queueConditions(array $filters, int $technicianId): array
    {
        $where  = [];
        $params = [];

        $status = $filters['status'] ?? 'open';
        if ($status === 'open') {
            $where[] = 's.code IN ' . self::OPEN;
        } elseif ($status !== 'all') {
            $where[]  = 's.code = ?';
            $params[] = $status;
        }

        if (!empty($filters['priority'])) {
            $where[]  = 'p.code = ?';
            $params[] = $filters['priority'];
        }
        if (($filters['location'] ?? '') !== '') {
            // « a102 » trouve « A102 » (collation insensible à la casse et aux accents) ;
            // % et _ saisis sont cherchés tels quels, pas interprétés comme jokers
            $where[]  = 'l.name LIKE ?';
            $params[] = '%' . addcslashes($filters['location'], '%_\\') . '%';
        }
        if (in_array($filters['level'] ?? '', ['1', '2', '3'], true)) {
            $where[]  = 't.support_level = ?';
            $params[] = (int) $filters['level'];
        }

        $scope = $filters['scope'] ?? 'all';
        if ($scope === 'mine') {
            $where[]  = 't.technician_id = ?';
            $params[] = $technicianId;
        } elseif ($scope === 'unassigned') {
            $where[] = 't.technician_id IS NULL';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    /** Tickets résolus depuis plus de $days jours, à clôturer automatiquement. */
    public static function resolvedSince(int $days): array
    {
        return Database::query(
            self::SELECT . " WHERE s.code = 'resolved' AND t.resolved_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        )->fetchAll();
    }

    // ------------------------------------------------------------------
    //  Écriture
    // ------------------------------------------------------------------

    /** Crée un ticket au statut « Nouveau » et renvoie son identifiant. */
    public static function create(
        int $userId,
        int $categoryId,
        int $locationId,
        int $priorityId,
        string $title,
        string $description
    ): int {
        return Database::transaction(static function () use ($userId, $categoryId, $locationId, $priorityId, $title, $description): int {
            $newStatus = Status::id('new');

            Database::query(
                'INSERT INTO tickets (user_id, category_id, location_id, priority_id, status_id, title, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $categoryId, $locationId, $priorityId, $newStatus, $title, $description]
            );
            $id = Database::lastInsertId();

            // Référence lisible, dérivée de l'identifiant : TT-2026-00042
            Database::query('UPDATE tickets SET reference = ? WHERE id = ?', [sprintf('TT-%s-%05d', date('Y'), $id), $id]);

            History::add($id, $userId, 'created', null, $newStatus);
            return $id;
        });
    }

    /**
     * Fait passer un ticket à un nouveau statut, si la transition est autorisée.
     *
     * @param int|null $actorId null = le système (clôture automatique)
     * @throws DomainException transition interdite, ou ticket modifié entre-temps par quelqu'un d'autre
     */
    public static function changeStatus(array $ticket, string $newCode, ?int $actorId, string $event = 'status'): void
    {
        $oldCode = $ticket['status_code'];
        if (!in_array($newCode, self::TRANSITIONS[$oldCode] ?? [], true)) {
            throw new DomainException("Transition interdite : $oldCode -> $newCode");
        }

        // Horodatages utilisés par les indicateurs (temps de résolution)
        $extra = match (true) {
            $newCode === 'resolved'                              => ', resolved_at = NOW()',
            $newCode === 'closed', $newCode === 'rejected'       => ', closed_at = NOW()',
            $newCode === 'in_progress' && $oldCode === 'resolved' => ', resolved_at = NULL', // réouverture
            default                                              => '',
        };

        Database::transaction(static function () use ($ticket, $newCode, $actorId, $event, $extra): void {
            $oldId = (int) $ticket['status_id'];
            $newId = Status::id($newCode);

            // « AND status_id = ancien » : si deux personnes agissent en même temps, une seule passe
            $updated = Database::query(
                "UPDATE tickets SET status_id = ? $extra WHERE id = ? AND status_id = ?",
                [$newId, $ticket['id'], $oldId]
            )->rowCount();

            if ($updated === 0) {
                throw new DomainException('Ticket modifié entre-temps');
            }
            History::add((int) $ticket['id'], $actorId, $event, $oldId, $newId);
        });
    }

    /** Un technicien s'affecte le ticket. Un ticket « Nouveau » passe alors « En cours ». */
    public static function take(array $ticket, int $technicianId): void
    {
        // Le changement de statut d'abord : s'il échoue (ticket déjà pris), rien n'est affecté
        if ($ticket['status_code'] === 'new') {
            self::changeStatus($ticket, 'in_progress', $technicianId);
        }

        Database::transaction(static function () use ($ticket, $technicianId): void {
            Database::query(
                'UPDATE tickets SET technician_id = ?, taken_at = COALESCE(taken_at, NOW()) WHERE id = ?',
                [$technicianId, $ticket['id']]
            );
            History::add((int) $ticket['id'], $technicianId, 'assigned');
        });
    }

    /** Escalade vers un niveau de support supérieur : le ticket redevient « à prendre » pour ce niveau. */
    public static function escalate(array $ticket, int $level, int $actorId): void
    {
        Database::transaction(static function () use ($ticket, $level, $actorId): void {
            Database::query(
                'UPDATE tickets SET support_level = ?, technician_id = NULL WHERE id = ?',
                [$level, $ticket['id']]
            );
            History::add(
                (int) $ticket['id'],
                $actorId,
                'escalated',
                null,
                null,
                sprintf('N%d → N%d', $ticket['support_level'], $level)
            );
        });
    }

    // ------------------------------------------------------------------
    //  Règles d'accès (appelées par les contrôleurs ET par les vues)
    // ------------------------------------------------------------------

    public static function isOpen(array $ticket): bool
    {
        return in_array($ticket['status_code'], ['new', 'in_progress', 'pending'], true);
    }

    /** Un déclarant ne voit que ses propres tickets ; techniciens et administrateurs les voient tous. */
    public static function canView(array $ticket, array $user): bool
    {
        return in_array($user['role_code'], ['technician', 'admin', 'superadmin'], true)
            || (int) $ticket['user_id'] === (int) $user['id'];
    }

    /** Le niveau du technicien doit couvrir le niveau de support du ticket. */
    public static function canHandle(array $ticket, array $user): bool
    {
        if (User::hasAdminRights($user)) {
            return true;
        }
        return $user['role_code'] === 'technician'
            && (int) $user['tech_level'] >= (int) $ticket['support_level'];
    }

    public static function canTake(array $ticket, array $user): bool
    {
        return self::canHandle($ticket, $user)
            && self::isOpen($ticket)
            && (int) $ticket['technician_id'] !== (int) $user['id'];
    }

    /** Seul le technicien en charge (ou un administrateur) fait avancer un ticket affecté. */
    public static function canChangeStatus(array $ticket, array $user): bool
    {
        return self::canHandle($ticket, $user)
            && ($ticket['technician_id'] === null
                || (int) $ticket['technician_id'] === (int) $user['id']
                || User::hasAdminRights($user));
    }

    /**
     * Statuts qu'un technicien peut choisir pour ce ticket.
     * « Clôturé » est réservé au déclarant (ou au délai de 7 jours) ; la prise en charge
     * d'un ticket « Nouveau » passe par le bouton dédié, pour qu'il ait toujours un responsable.
     */
    public static function staffTargets(array $ticket): array
    {
        return array_values(array_filter(
            self::TRANSITIONS[$ticket['status_code']] ?? [],
            static fn (string $code): bool => $code !== 'closed'
                && !($ticket['status_code'] === 'new' && $code === 'in_progress')
        ));
    }
}
