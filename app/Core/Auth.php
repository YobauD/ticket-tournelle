<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Log;
use App\Models\User;

/**
 * Authentification par comptes locaux et connaissance de l'utilisateur connecté.
 *
 * Toute la logique de connexion est isolée ici : brancher plus tard l'annuaire de l'ENT
 * (CAS, LDAP) reviendra à remplacer attempt() sans toucher aux contrôleurs.
 */
final class Auth
{
    public const OK      = 'ok';
    public const INVALID = 'invalid';
    public const LOCKED  = 'locked';

    private static ?array $user = null;
    private static bool $loaded = false;

    /**
     * Utilisateur connecté, relu en base à chaque requête : un changement de rôle
     * ou une désactivation par l'administrateur prend effet immédiatement.
     */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id !== null) {
                $user = User::find((int) $id);
                if ($user !== null && (int) $user['is_active'] === 1) {
                    self::$user = $user;
                } else {
                    Session::forget('user_id'); // compte supprimé ou désactivé
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) self::$user['id'] : null;
    }

    /** Vrai si l'utilisateur connecté possède l'un des rôles donnés. */
    public static function is(string ...$roles): bool
    {
        return self::check() && in_array(self::$user['role_code'], $roles, true);
    }

    /** Administrateur ou superadministrateur. */
    public static function isAdmin(): bool
    {
        return self::is(...User::ADMIN_ROLES);
    }

    public static function isStaff(): bool
    {
        return self::is('technician', 'admin', 'superadmin');
    }

    /**
     * Tente une connexion. Renvoie Auth::OK, Auth::INVALID ou Auth::LOCKED.
     * Le message d'erreur est volontairement le même pour « e-mail inconnu » et
     * « mauvais mot de passe » afin de ne pas révéler quels comptes existent.
     */
    public static function attempt(string $username, string $password): string
    {
        $user = User::findByUsername($username);

        if ($user === null || (int) $user['is_active'] !== 1) {
            // Vérification factice : le temps de réponse ne trahit pas l'existence du compte
            password_verify($password, '$2y$10$JAoUvp7qAXn28ue1Ezhg8uaMg4IslUB07SVfhw1hOl8Ff98o1nL5a');
            Log::write('login_failed', 'login:' . mb_substr($username, 0, 80));
            return self::INVALID;
        }

        if ((int) $user['is_locked'] === 1) {
            Log::write('login_blocked', 'user:' . $user['id']);
            return self::LOCKED;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $locked = User::registerFailedLogin(
                (int) $user['id'],
                (int) Config::get('security.max_login_attempts'),
                (int) Config::get('security.lockout_minutes')
            );
            Log::write($locked ? 'account_locked' : 'login_failed', 'user:' . $user['id']);
            return $locked ? self::LOCKED : self::INVALID;
        }

        // --- Connexion réussie ---
        // Si l'algorithme ou son coût a évolué depuis la création du compte, on re-hache
        if (password_needs_rehash($user['password_hash'], self::algorithm())) {
            User::setPassword((int) $user['id'], self::hash($password), (bool) $user['must_change_password']);
        }

        Session::regenerate();                       // anti-fixation de session
        Session::set('user_id', (int) $user['id']);
        User::registerSuccessfulLogin((int) $user['id']);
        Lang::set($user['lang']);                    // l'interface reprend la langue préférée du compte
        self::$loaded = false;

        Log::write('login_success', 'user:' . $user['id'], null, (int) $user['id']);
        return self::OK;
    }

    public static function logout(): void
    {
        if (self::check()) {
            Log::write('logout', 'user:' . self::id());
        }
        $lang = Lang::locale();
        Session::destroy();
        session_start();
        Session::set('lang', $lang);                 // on garde la langue sur la page de connexion
        self::$user = null;
    }

    /** Argon2id si PHP le propose, BCrypt sinon. */
    public static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    /**
     * Contrôle la politique de mot de passe. Renvoie la clé de traduction de l'erreur, ou null si tout va bien.
     */
    /**
     * @param string ...$identifiers identifiant et adresse e-mail du compte : le mot de passe
     *                               ne peut être ni l'un ni l'autre
     */
    public static function passwordError(string $password, string ...$identifiers): ?string
    {
        if (mb_strlen($password) < (int) Config::get('security.password_min_length')) {
            return 'password.too_short';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
            return 'password.too_simple';
        }
        foreach ($identifiers as $identifier) {
            if ($identifier !== '' && mb_strtolower($password) === mb_strtolower($identifier)) {
                return 'password.is_identifier';
            }
        }
        return null;
    }
}
