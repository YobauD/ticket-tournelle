<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Log;
use App\Models\User;
use App\Services\LoginThrottle;

/**
 * Authentification par comptes locaux et connaissance de l'utilisateur connecté.
 *
 * Toute la logique de connexion est isolée ici : brancher plus tard l'annuaire de l'ENT
 * (CAS, LDAP) reviendra à remplacer attempt() sans toucher aux contrôleurs.
 */
final class Auth
{
    public const OK        = 'ok';
    public const INVALID   = 'invalid';
    public const LOCKED    = 'locked';      // ce compte, depuis ce poste (ou le compte entier)
    public const THROTTLED = 'throttled';   // ce poste, tous comptes confondus

    /** Mots et motifs refusés dans un mot de passe : les premiers essayés par un attaquant. */
    private const COMMON_WORDS = [
        'password', 'motdepasse', 'azerty', 'qwerty', 'abc123', '123456', '000000', 'soleil',
        'bonjour', 'tournelle', 'garenne', 'lycee', 'provisoire',
    ];

    /** Caractères des mots de passe provisoires : sans 0/O, 1/l/I, faciles à dicter. */
    private const TEMP_ALPHABET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private static ?array $user = null;
    private static bool $loaded = false;

    /**
     * Utilisateur connecté, relu en base à chaque requête : un changement de rôle
     * ou une désactivation par l'administrateur prend effet immédiatement.
     * Si le mot de passe a changé depuis l'ouverture de la session (session_version), la
     * session est fermée : une session volée ne survit pas à un changement de mot de passe.
     */
    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id !== null) {
                $user = User::find((int) $id);
                if ($user === null || (int) $user['is_active'] !== 1) {
                    Session::forget('user_id'); // compte supprimé ou désactivé
                } elseif ((int) $user['session_version'] !== (int) Session::get('session_version', -1)) {
                    Session::forget('user_id');  // mot de passe changé depuis : session fermée
                    Session::flash('info', t('auth.session_revoked'));
                } else {
                    self::$user = $user;
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
     * Tente une connexion. Renvoie Auth::OK, INVALID, LOCKED ou THROTTLED.
     * Le message d'erreur est le même pour « identifiant inconnu » et « mauvais mot de passe »,
     * et le temps de réponse aussi : la page ne révèle pas quels comptes existent.
     * Limites (LoginThrottle) : par compte et par poste, par poste, et par compte.
     */
    public static function attempt(string $username, string $password): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (LoginThrottle::ipBlocked($ip)) {
            password_verify($password, self::DUMMY_HASH);
            Log::write('login_throttled', 'login:' . mb_substr($username, 0, 80));
            return self::THROTTLED;
        }

        $user = User::findByUsername($username);

        if ($user === null || (int) $user['is_active'] !== 1) {
            // Vérification factice : le temps de réponse ne trahit pas l'existence du compte
            password_verify($password, self::DUMMY_HASH);
            Log::write('login_failed', 'login:' . mb_substr($username, 0, 80));
            return self::INVALID;
        }

        // Compte entier bloqué (attaque depuis de nombreux postes), ou ce poste bloqué pour ce compte
        if ((int) $user['is_locked'] === 1 || LoginThrottle::pairBlocked($user, $ip)) {
            password_verify($password, self::DUMMY_HASH);
            Log::write('login_blocked', 'user:' . $user['id']);
            return self::LOCKED;
        }

        if (!password_verify($password, $user['password_hash'])) {
            Log::write('login_failed', 'user:' . $user['id']);
            if (LoginThrottle::accountUnderAttack($user)) {
                User::lock((int) $user['id'], LoginThrottle::minutes());
                Log::write('account_locked', 'user:' . $user['id'], 'échecs depuis plusieurs postes');
                return self::LOCKED;
            }
            return LoginThrottle::pairBlocked($user, $ip) ? self::LOCKED : self::INVALID;
        }

        // --- Connexion réussie ---
        // Si l'algorithme ou son coût a évolué depuis la création du compte, on re-hache
        if (password_needs_rehash($user['password_hash'], self::algorithm())) {
            User::rehash((int) $user['id'], self::hash($password));
        }

        Session::regenerate();                       // anti-fixation de session
        Session::set('user_id', (int) $user['id']);
        Session::set('session_version', (int) $user['session_version']);
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
    /** Hachage d'un mot de passe quelconque, pour les vérifications factices. */
    private const DUMMY_HASH = '$2y$10$JAoUvp7qAXn28ue1Ezhg8uaMg4IslUB07SVfhw1hOl8Ff98o1nL5a';

    /**
     * Après un changement de son propre mot de passe : la session en cours reprend la nouvelle
     * version, les autres sessions du compte (autre poste, session volée) sont fermées.
     */
    public static function keepCurrentSession(): void
    {
        $user = User::find((int) Session::get('user_id'));
        if ($user !== null) {
            Session::set('session_version', (int) $user['session_version']);
        }
        self::$loaded = false;
    }

    /**
     * Mot de passe provisoire aléatoire, conforme à la politique : 12 caractères tirés au hasard
     * (environ 69 bits), en trois groupes faciles à dicter, par exemple « Kx7m-p4Rt-9wzQ ».
     */
    public static function temporaryPassword(): string
    {
        $max = strlen(self::TEMP_ALPHABET) - 1;
        do {
            $chars = '';
            for ($i = 0; $i < 12; $i++) {
                $chars .= self::TEMP_ALPHABET[random_int(0, $max)];
            }
        } while (!preg_match('/[a-z]/', $chars) || !preg_match('/[A-Z]/', $chars) || !preg_match('/\d/', $chars));
        return implode('-', str_split($chars, 4));
    }

    public static function passwordError(string $password, string ...$identifiers): ?string
    {
        if (mb_strlen($password) < (int) Config::get('security.password_min_length')) {
            return 'password.too_short';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
            return 'password.too_simple';
        }
        $lower = mb_strtolower($password);
        foreach ($identifiers as $identifier) {
            if ($identifier !== '' && $lower === mb_strtolower($identifier)) {
                return 'password.is_identifier';
            }
        }
        // Mot courant ou répétition (« Azerty123456 », « Aaaaaaaaaaa1 ») : premiers essais d'un attaquant
        $letters = (string) preg_replace('/[^a-z0-9]/', '', fold_accents($password));
        foreach (self::COMMON_WORDS as $word) {
            if (str_contains($letters, $word)) {
                return 'password.too_common';
            }
        }
        if (preg_match('/(.)\1{3,}/u', $password)) {
            return 'password.too_common';
        }
        return null;
    }
}
