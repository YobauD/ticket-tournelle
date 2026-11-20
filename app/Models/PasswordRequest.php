<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\AccountPolicy;

/**
 * Demandes de nouveau mot de passe, faites depuis la page de connexion.
 *
 * Le portail n'envoie jamais de mot de passe par e-mail : la demande prévient les
 * administrateurs, qui vérifient l'identité de la personne puis lui remettent un mot de
 * passe provisoire (Administration > Utilisateurs). Une seule demande en attente par compte.
 */
final class PasswordRequest
{
    public const MESSAGE_MAX = 300;

    /** Codes du journal comptés pour limiter les demandes venant d'un même poste. */
    private const LOG_ACTIONS = "('password_request', 'password_request_unknown')";

    public static function hasPending(int $userId): bool
    {
        return (bool) Database::query(
            "SELECT 1 FROM password_requests WHERE user_id = ? AND status = 'pending'",
            [$userId]
        )->fetchColumn();
    }

    public static function create(int $userId, ?string $message): int
    {
        Database::query(
            'INSERT INTO password_requests (user_id, message, ip_address) VALUES (?, ?, ?)',
            [$userId, $message, $_SERVER['REMOTE_ADDR'] ?? null]
        );
        return Database::lastInsertId();
    }

    /**
     * Demandes envoyées depuis cette adresse IP ces $minutes dernières minutes, adresse
     * inconnue comprise. Le journal d'audit sert de compteur : chaque demande y est inscrite.
     */
    public static function recentFromIp(string $ip, int $minutes): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM logs
              WHERE action IN ' . self::LOG_ACTIONS . '
                AND ip_address = ?
                AND created_at > NOW() - INTERVAL ? MINUTE',
            [$ip, $minutes]
        )->fetchColumn();
    }

    /**
     * Demandes en attente sur des comptes actifs, avec le compte concerné.
     * Chaque ligne a les colonnes d'un compte (id, role_code…) pour AccountPolicy,
     * et celles de la demande préfixées request_.
     */
    public static function pending(): array
    {
        return Database::query(
            "SELECT pr.id AS request_id, pr.message AS request_message, pr.created_at AS request_at,
                    u.id, u.first_name, u.last_name, u.email, u.lang, u.job_title_id, u.tech_level,
                    r.code AS role_code, r.label_fr AS role_fr, r.label_en AS role_en,
                    j.label_fr AS job_title_fr, j.label_en AS job_title_en
               FROM password_requests pr
               JOIN users u ON u.id = pr.user_id
               JOIN roles r ON r.id = u.role_id
               LEFT JOIN job_titles j ON j.id = u.job_title_id
              WHERE pr.status = 'pending' AND u.is_active = 1
              ORDER BY pr.created_at"
        )->fetchAll();
    }

    /** Demandes en attente que $actor peut traiter (écran des comptes, pastilles). */
    public static function pendingFor(array $actor): array
    {
        return array_values(array_filter(
            self::pending(),
            static fn (array $row): bool => AccountPolicy::seesResetRequest($actor, $row)
        ));
    }

    public static function countFor(array $actor): int
    {
        return count(self::pendingFor($actor));
    }

    /** Une demande en attente, avec son compte (mêmes colonnes que pending()). */
    public static function findPending(int $requestId): ?array
    {
        foreach (self::pending() as $row) {
            if ((int) $row['request_id'] === $requestId) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Clôt la demande en attente d'un compte, s'il y en a une.
     * $status : 'done' (mot de passe réinitialisé) ou 'cancelled' (la personne s'est reconnectée).
     * @return bool vrai si une demande était en attente
     */
    public static function closeFor(int $userId, string $status, ?int $handlerId): bool
    {
        return Database::query(
            "UPDATE password_requests
                SET status = ?, handled_by = ?, handled_at = NOW()
              WHERE user_id = ? AND status = 'pending'",
            [$status, $handlerId, $userId]
        )->rowCount() > 0;
    }

    public static function dismiss(int $requestId, int $handlerId): void
    {
        Database::query(
            "UPDATE password_requests
                SET status = 'dismissed', handled_by = ?, handled_at = NOW()
              WHERE id = ? AND status = 'pending'",
            [$handlerId, $requestId]
        );
    }
}
