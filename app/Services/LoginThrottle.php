<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Limite les tentatives de connexion, à partir des échecs inscrits au journal d'audit.
 *
 * Trois compteurs, sur les lockout_minutes dernières minutes :
 *  - un compte depuis un même poste (max_login_attempts) : ce poste ne peut plus essayer ce
 *    compte. Un attaquant ne bloque donc pas la vraie personne, qui se connecte d'un autre poste ;
 *  - un même poste, tous comptes confondus (ip_max_failures) : contre l'essai d'un mot de passe
 *    courant sur tous les comptes (« password spraying ») ;
 *  - un compte, tous postes confondus (account_max_failures, seuil élevé) : contre une attaque
 *    répartie sur de nombreux postes ; le compte entier est alors bloqué.
 * Une connexion réussie remet à zéro les compteurs du compte.
 */
final class LoginThrottle
{
    /** Ce poste a-t-il trop échoué, tous comptes confondus ? */
    public static function ipBlocked(string $ip): bool
    {
        return self::count('ip_address = ?', [$ip]) >= (int) Config::get('security.ip_max_failures', 20);
    }

    /** Ce poste a-t-il trop échoué sur ce compte ? */
    public static function pairBlocked(array $user, string $ip): bool
    {
        return self::countForUser($user, 'AND ip_address = ?', [$ip]) >= (int) Config::get('security.max_login_attempts', 5);
    }

    /** Le compte a-t-il trop d'échecs, tous postes confondus ? */
    public static function accountUnderAttack(array $user): bool
    {
        return self::countForUser($user, '', []) >= (int) Config::get('security.account_max_failures', 50);
    }

    public static function minutes(): int
    {
        return (int) Config::get('security.lockout_minutes', 15);
    }

    /** Échecs sur ce compte depuis sa dernière connexion réussie (et dans la fenêtre). */
    private static function countForUser(array $user, string $extra, array $params): int
    {
        return self::count(
            "target = ? AND created_at > COALESCE(?, '1970-01-01') $extra",
            array_merge(['user:' . $user['id'], $user['last_login_at']], $params)
        );
    }

    private static function count(string $where, array $params): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM logs
              WHERE action IN ('login_failed', 'account_locked')
                AND created_at > NOW() - INTERVAL ? MINUTE
                AND $where",
            array_merge([self::minutes()], $params)
        )->fetchColumn();
    }
}
