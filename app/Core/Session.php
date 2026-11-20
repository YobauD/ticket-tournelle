<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session PHP durcie + messages « flash » (affichés une seule fois).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        ini_set('session.use_strict_mode', '1');   // refuse les identifiants de session non générés par le serveur
        ini_set('session.use_only_cookies', '1');  // jamais d'identifiant de session dans l'URL
        session_name(Config::get('security.session_name'));
        session_set_cookie_params([
            'lifetime' => 0,          // cookie supprimé à la fermeture du navigateur
            'path'     => '/',
            'secure'   => $https,     // envoyé uniquement en HTTPS quand le site est en HTTPS
            'httponly' => true,       // illisible en JavaScript (limite l'impact d'une XSS)
            'samesite' => 'Lax',      // non envoyé sur les POST venant d'un autre site (CSRF)
        ]);
        session_start();

        // Déconnexion automatique après une période d'inactivité
        $timeout = (int) Config::get('security.session_timeout');
        if (isset($_SESSION['user_id'], $_SESSION['last_activity'])
            && time() - $_SESSION['last_activity'] > $timeout) {
            $lang = $_SESSION['lang'] ?? null;
            self::destroy();
            session_start();
            if ($lang !== null) {
                $_SESSION['lang'] = $lang;
            }
            $_SESSION['expired'] = true;
        }
        $_SESSION['last_activity'] = time();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Nouvel identifiant de session : à appeler à chaque connexion (anti-fixation). */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }

    /** Enregistre un message à afficher sur la prochaine page. $type : success | error | info */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Renvoie les messages en attente et les efface. */
    public static function pullFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
