<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /** Rôles qui donnent accès à l'administration (comptes, référentiels, journal, statistiques). */
    public const ADMIN_ROLES = ['admin', 'superadmin'];

    public static function hasAdminRights(array $user): bool
    {
        return in_array($user['role_code'], self::ADMIN_ROLES, true);
    }

    /** Sélection commune : le compte, son rôle, sa fonction et l'état du verrouillage (calculé par MySQL). */
    private const SELECT = '
        SELECT u.*, r.code AS role_code, r.label_fr AS role_fr, r.label_en AS role_en,
               j.label_fr AS job_title_fr, j.label_en AS job_title_en,
               (u.locked_until IS NOT NULL AND u.locked_until > NOW()) AS is_locked
          FROM users u
          JOIN roles r ON r.id = u.role_id
          LEFT JOIN job_titles j ON j.id = u.job_title_id';

    public static function find(int $id): ?array
    {
        return Database::query(self::SELECT . ' WHERE u.id = ?', [$id])->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::query(self::SELECT . ' WHERE u.email = ?', [mb_strtolower(trim($email))])->fetch() ?: null;
    }

    public static function all(): array
    {
        return Database::query(self::SELECT . ' ORDER BY u.last_name, u.first_name')->fetchAll();
    }

    /** Comptes actifs ayant les droits d'administration (destinataires des demandes de mot de passe). */
    public static function activeAdmins(): array
    {
        return Database::query(
            self::SELECT . " WHERE r.code IN ('admin', 'superadmin') AND u.is_active = 1 ORDER BY u.id"
        )->fetchAll();
    }

    public static function roles(): array
    {
        return Database::query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    /** Crée un compte avec un mot de passe provisoire, à changer à la première connexion. */
    public static function create(
        int $roleId,
        string $lastName,
        string $firstName,
        string $email,
        string $passwordHash,
        ?int $techLevel,
        string $lang = 'fr',
        ?int $jobTitleId = null
    ): int {
        Database::query(
            'INSERT INTO users (role_id, job_title_id, last_name, first_name, email, password_hash, tech_level, lang, must_change_password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [$roleId, $jobTitleId, $lastName, $firstName, mb_strtolower($email), $passwordHash, $techLevel, $lang]
        );
        return Database::lastInsertId();
    }

    public static function updateAccess(int $id, int $roleId, ?int $techLevel, bool $isActive): void
    {
        Database::query(
            'UPDATE users SET role_id = ?, tech_level = ?, is_active = ? WHERE id = ?',
            [$roleId, $techLevel, (int) $isActive, $id]
        );
    }

    /** Fonction au lycée (Professeur, Direction…), null pour « non renseignée ». */
    public static function setJobTitle(int $id, ?int $jobTitleId): void
    {
        Database::query('UPDATE users SET job_title_id = ? WHERE id = ?', [$jobTitleId, $id]);
    }

    /** Enregistre un nouveau hachage et lève tout verrouillage en cours. */
    public static function setPassword(int $id, string $hash, bool $mustChange): void
    {
        Database::query(
            'UPDATE users
                SET password_hash = ?, must_change_password = ?, failed_attempts = 0, locked_until = NULL
              WHERE id = ?',
            [$hash, (int) $mustChange, $id]
        );
    }

    public static function setLang(int $id, string $lang): void
    {
        Database::query('UPDATE users SET lang = ? WHERE id = ?', [$lang, $id]);
    }

    /**
     * Compte un échec de connexion. Au-delà de $max échecs, le compte est verrouillé
     * $minutes minutes (frein aux attaques par force brute). Renvoie true s'il vient de l'être.
     */
    public static function registerFailedLogin(int $id, int $max, int $minutes): bool
    {
        Database::query('UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?', [$id]);
        $attempts = (int) Database::query('SELECT failed_attempts FROM users WHERE id = ?', [$id])->fetchColumn();

        if ($attempts < $max) {
            return false;
        }
        Database::query(
            'UPDATE users SET failed_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
            [$minutes, $id]
        );
        return true;
    }

    public static function registerSuccessfulLogin(int $id): void
    {
        Database::query(
            'UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?',
            [$id]
        );
    }
}
