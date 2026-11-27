<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /** Rôles qui donnent accès à l'administration (comptes, référentiels, journal, statistiques). */
    public const ADMIN_ROLES = ['admin', 'superadmin'];

    /**
     * Identifiant de connexion : 2 à 50 caractères, minuscules sans accents, chiffres, point,
     * tiret ou tiret bas, commençant et finissant par une lettre ou un chiffre (marie.dupont).
     * L'adresse e-mail, elle, ne sert qu'aux notifications.
     */
    public const USERNAME_PATTERN = '/^[a-z0-9](?:[a-z0-9._-]{0,48}[a-z0-9])?$/';

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

    /** Compte par identifiant de connexion (comparaison insensible à la casse). */
    public static function findByUsername(string $username): ?array
    {
        return Database::query(self::SELECT . ' WHERE u.username = ?', [mb_strtolower(trim($username))])->fetch() ?: null;
    }

    /** Identifiant, ou adresse e-mail si la saisie contient un @ (formulaire « mot de passe oublié »). */
    public static function findByUsernameOrEmail(string $value): ?array
    {
        return str_contains($value, '@') ? self::findByEmail($value) : self::findByUsername($value);
    }

    /** Identifiant déjà pris par un autre compte que $exceptId ? */
    public static function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        return (bool) Database::query(
            'SELECT 1 FROM users WHERE username = ? AND id <> ?',
            [mb_strtolower($username), $exceptId ?? 0]
        )->fetchColumn();
    }

    /** Adresse e-mail déjà utilisée par un autre compte que $exceptId ? */
    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) Database::query(
            'SELECT 1 FROM users WHERE email = ? AND id <> ?',
            [mb_strtolower($email), $exceptId ?? 0]
        )->fetchColumn();
    }

    /** Corrige l'identifiant de connexion et l'adresse e-mail de notification d'un compte. */
    public static function setIdentity(int $id, string $username, string $email): void
    {
        Database::query(
            'UPDATE users SET username = ?, email = ? WHERE id = ?',
            [mb_strtolower($username), mb_strtolower($email), $id]
        );
    }

    /**
     * Identifiant proposé quand l'administrateur n'en saisit pas : prenom.nom sans accents
     * (Hélène Le Gall -> helene.le-gall), suivi d'un numéro s'il est déjà pris (marie.dupont2).
     */
    public static function suggestUsername(string $firstName, string $lastName): string
    {
        $part = static fn (string $name): string => trim(
            (string) preg_replace(['/[\s\']+/', '/[^a-z0-9-]+/', '/-{2,}/'], ['-', '', '-'], fold_accents(trim($name))),
            '-'
        );
        $base = mb_substr(trim($part($firstName) . '.' . $part($lastName), '.'), 0, 45);
        $base = $base !== '' ? $base : 'utilisateur';

        $candidate = $base;
        for ($n = 2; self::usernameTaken($candidate); $n++) {
            $candidate = $base . $n;
        }
        return $candidate;
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
        string $username,
        string $lastName,
        string $firstName,
        string $email,
        string $passwordHash,
        ?int $techLevel,
        string $lang = 'fr',
        ?int $jobTitleId = null
    ): int {
        Database::query(
            'INSERT INTO users (role_id, job_title_id, username, last_name, first_name, email, password_hash, tech_level, lang, must_change_password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [$roleId, $jobTitleId, mb_strtolower($username), $lastName, $firstName, mb_strtolower($email), $passwordHash, $techLevel, $lang]
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
