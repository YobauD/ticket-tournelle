<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Log;

/**
 * Durées de conservation des données personnelles (RGPD, article 5.1.e : « limitation de la conservation »).
 *
 * Lancé chaque nuit par scripts/purge.php. Chaque règle renvoie le nombre d'éléments concernés ;
 * en simulation ($apply = false), rien n'est modifié et les nombres disent ce qui le serait.
 * Les durées se règlent dans config.php, section 'retention'.
 */
final class DataRetention
{
    /** Préfixe de l'identifiant d'un compte anonymisé (ancien-compte-12). */
    public const ANONYMOUS_PREFIX = 'ancien-compte-';

    /**
     * Applique toutes les règles.
     * @return array<string, int> nombre d'éléments par règle, dans l'ordre d'application
     */
    public static function run(bool $apply): array
    {
        $result = [
            'connection_logs'   => self::connectionLogs($apply),
            'audit_logs'        => self::auditLogs($apply),
            'audit_ips'         => self::auditIps($apply),
            'email_tokens'      => self::emailTokens($apply),
            'password_requests' => self::passwordRequests($apply),
            'closed_tickets'    => self::closedTickets($apply),
            'disabled_accounts' => self::disabledAccounts($apply),
            'orphan_files'      => self::orphanFiles($apply),
            'mail_log'          => self::mailLog($apply),
        ];

        // Une ligne de journal par passage utile : la purge elle-même reste traçable
        if ($apply && array_sum($result) > 0) {
            $done = array_filter($result);
            Log::write('data_purged', null, implode(', ', array_map(
                static fn (string $rule, int $count): string => "$rule: $count",
                array_keys($done),
                $done
            )));
        }

        return $result;
    }

    /** Date limite : tout ce qui est plus ancien est concerné. */
    public static function cutoff(string $key, string $unit): string
    {
        $amount = max(1, (int) Config::get('retention.' . $key));
        return (new \DateTimeImmutable("-$amount $unit"))->format('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------
    //  Journal
    // ------------------------------------------------------------------

    /** Connexions, mots de passe, refus d'accès : supprimés après connection_logs_months. */
    private static function connectionLogs(bool $apply): int
    {
        [$in, $actions] = self::auditList();
        return self::countOrDelete(
            $apply,
            "FROM logs WHERE action NOT IN ($in) AND created_at < ?",
            [...$actions, self::cutoff('connection_logs_months', 'months')]
        );
    }

    /** Actions d'administration : supprimées après audit_logs_months. */
    private static function auditLogs(bool $apply): int
    {
        [$in, $actions] = self::auditList();
        return self::countOrDelete(
            $apply,
            "FROM logs WHERE action IN ($in) AND created_at < ?",
            [...$actions, self::cutoff('audit_logs_months', 'months')]
        );
    }

    /**
     * Les actions d'administration gardées plus longtemps perdent leur adresse IP.
     * Les autres lignes de cet âge sont supprimées par connectionLogs() : la simulation ne les compte pas ici.
     */
    private static function auditIps(bool $apply): int
    {
        [$in, $actions] = self::auditList();
        $where  = "WHERE ip_address IS NOT NULL AND action IN ($in) AND created_at < ? AND created_at >= ?";
        $params = [...$actions, self::cutoff('connection_logs_months', 'months'), self::cutoff('audit_logs_months', 'months')];
        if (!$apply) {
            return (int) Database::query("SELECT COUNT(*) FROM logs $where", $params)->fetchColumn();
        }
        return Database::query("UPDATE logs SET ip_address = NULL $where", $params)->rowCount();
    }

    /** @return array{0: string, 1: list<string>} marques « ?, ?, … » et liste des actions d'administration */
    private static function auditList(): array
    {
        return [implode(', ', array_fill(0, count(Log::AUDIT_ACTIONS), '?')), Log::AUDIT_ACTIONS];
    }

    // ------------------------------------------------------------------
    //  Liens e-mail et demandes de mot de passe
    // ------------------------------------------------------------------

    /** Liens utilisés ou expirés depuis plus de email_tokens_days. */
    private static function emailTokens(bool $apply): int
    {
        return self::countOrDelete(
            $apply,
            'FROM email_tokens WHERE COALESCE(used_at, expires_at) < ?',
            [self::cutoff('email_tokens_days', 'days')]
        );
    }

    /** Demandes traitées ou ignorées depuis plus de password_requests_months (les demandes en attente restent). */
    private static function passwordRequests(bool $apply): int
    {
        return self::countOrDelete(
            $apply,
            "FROM password_requests WHERE status <> 'pending' AND COALESCE(handled_at, created_at) < ?",
            [self::cutoff('password_requests_months', 'months')]
        );
    }

    // ------------------------------------------------------------------
    //  Tickets
    // ------------------------------------------------------------------

    /**
     * Tickets clôturés ou rejetés depuis plus de closed_tickets_months. Leurs échanges, leur
     * historique et leurs pièces jointes sont supprimés avec eux (clés étrangères ON DELETE CASCADE),
     * puis les fichiers déposés sont effacés du disque.
     */
    private static function closedTickets(bool $apply): int
    {
        $ids = Database::query(
            'SELECT t.id FROM tickets t
               JOIN status s ON s.id = t.status_id
              WHERE s.is_final = 1
                AND COALESCE(t.closed_at, t.resolved_at, t.updated_at) < ?',
            [self::cutoff('closed_tickets_months', 'months')]
        )->fetchAll(\PDO::FETCH_COLUMN);

        if (!$apply || $ids === []) {
            return count($ids);
        }

        $dir = (string) Config::get('uploads.dir');
        foreach (array_chunk($ids, 500) as $chunk) {
            $in    = implode(', ', array_fill(0, count($chunk), '?'));
            $files = Database::transaction(static function () use ($in, $chunk): array {
                $files = Database::query("SELECT stored_name FROM ticket_attachments WHERE ticket_id IN ($in)", $chunk)
                    ->fetchAll(\PDO::FETCH_COLUMN);
                Database::query("DELETE FROM tickets WHERE id IN ($in)", $chunk);
                return $files;
            });
            // Après la validation seulement : un fichier qui resterait serait repris par orphanFiles()
            foreach ($files as $name) {
                $path = $dir . '/' . basename((string) $name);
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return count($ids);
    }

    /** Fichiers déposés qui ne correspondent plus à aucune pièce jointe (envoi interrompu, purge incomplète). */
    private static function orphanFiles(bool $apply): int
    {
        $dir = (string) Config::get('uploads.dir');
        if (!is_dir($dir)) {
            return 0;
        }
        $known   = array_flip(Database::query('SELECT stored_name FROM ticket_attachments')->fetchAll(\PDO::FETCH_COLUMN));
        $dayAgo  = time() - 86400;   // un fichier en cours d'envoi n'a pas encore sa ligne en base
        $removed = 0;
        foreach (scandir($dir) ?: [] as $name) {
            $path = $dir . '/' . $name;
            if ($name[0] === '.' || !is_file($path) || isset($known[$name]) || filemtime($path) > $dayAgo) {
                continue;
            }
            if ($apply) {
                unlink($path);
            }
            $removed++;
        }
        return $removed;
    }

    // ------------------------------------------------------------------
    //  Comptes
    // ------------------------------------------------------------------

    /**
     * Comptes désactivés depuis plus de disabled_accounts_months : nom, identifiant, adresse e-mail
     * et fonction sont effacés, le mot de passe est rendu inutilisable. Le compte reste en base pour
     * que les tickets et le journal gardent un auteur (« Ancien compte »), sans plus désigner personne.
     * Les comptes superadministrateur, gérés sur le serveur, ne sont jamais concernés.
     */
    private static function disabledAccounts(bool $apply): int
    {
        $ids = Database::query(
            "SELECT u.id FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.is_active = 0
                AND u.deactivated_at < ?
                AND r.code <> 'superadmin'
                AND u.username NOT LIKE ?",
            [self::cutoff('disabled_accounts_months', 'months'), self::ANONYMOUS_PREFIX . '%']
        )->fetchAll(\PDO::FETCH_COLUMN);

        if (!$apply) {
            return count($ids);
        }

        foreach ($ids as $id) {
            $id = (int) $id;
            Database::transaction(static function () use ($id): void {
                Database::query(
                    "UPDATE users
                        SET username = ?, first_name = 'Ancien', last_name = 'compte', email = NULL,
                            job_title_id = NULL, password_hash = '!', must_change_password = 1,
                            failed_attempts = 0, locked_until = NULL, last_login_at = NULL,
                            session_version = session_version + 1
                      WHERE id = ?",
                    [self::ANONYMOUS_PREFIX . $id, $id]
                );
                Database::query('DELETE FROM email_tokens WHERE user_id = ?', [$id]);
                Database::query('DELETE FROM password_requests WHERE user_id = ?', [$id]);
            });
            Log::write('account_anonymized', 'user:' . $id);
        }

        return count($ids);
    }

    /** Le compte a-t-il déjà été anonymisé ? */
    public static function isAnonymized(array $user): bool
    {
        return str_starts_with((string) $user['username'], self::ANONYMOUS_PREFIX);
    }

    /** Date à laquelle un compte désactivé sera anonymisé, ou null s'il est actif. */
    public static function anonymizationDate(array $user): ?string
    {
        if ((int) $user['is_active'] === 1 || empty($user['deactivated_at'])) {
            return null;
        }
        $months = max(1, (int) Config::get('retention.disabled_accounts_months'));
        return (new \DateTimeImmutable((string) $user['deactivated_at']))->modify("+$months months")->format('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------
    //  Fichier mail.log (pilote 'log', développement)
    // ------------------------------------------------------------------

    /** Retire de mail.log les e-mails de plus de mail_log_days (ils contiennent noms, adresses et liens). */
    private static function mailLog(bool $apply): int
    {
        $path = BASE_PATH . '/storage/logs/mail.log';
        if (!is_file($path)) {
            return 0;
        }
        $content = (string) file_get_contents($path);
        // Chaque e-mail commence par une ligne « ==== AAAA-MM-JJ HH:MM:SS ==== »
        $blocks = preg_split('/^(?===== \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} ====$)/m', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $cutoff = self::cutoff('mail_log_days', 'days');
        $kept   = [];
        $old    = 0;
        foreach ($blocks as $block) {
            if (preg_match('/^==== (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) ====/', $block, $m) && $m[1] < $cutoff) {
                $old++;
                continue;
            }
            $kept[] = $block;
        }
        if ($apply && $old > 0) {
            file_put_contents($path, implode('', $kept), LOCK_EX);
        }
        return $old;
    }

    // ------------------------------------------------------------------

    /** Compte les lignes visées ($from = « FROM … WHERE … »), ou les supprime. */
    private static function countOrDelete(bool $apply, string $from, array $params): int
    {
        if (!$apply) {
            return (int) Database::query("SELECT COUNT(*) $from", $params)->fetchColumn();
        }
        return Database::query("DELETE $from", $params)->rowCount();
    }
}
