<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF : chaque formulaire POST embarque un jeton secret lié à la session.
 * Un site tiers ne peut pas le deviner, donc ne peut pas forger de requête à la place de l'utilisateur.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Vérifie le jeton reçu (comparaison à temps constant). */
    public static function check(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent) && $sent !== '' && hash_equals(self::token(), $sent);
    }
}
