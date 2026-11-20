<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Accès en lecture à la configuration : Config::get('db.host').
 */
final class Config
{
    private static array $items = [];

    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /** Modifie une clé en notation pointée (utilisé au démarrage pour les valeurs détectées). */
    public static function set(string $key, mixed $value): void
    {
        $ref = &self::$items;
        foreach (explode('.', $key) as $segment) {
            $ref = &$ref[$segment];
        }
        $ref = $value;
    }

    /** Lit une clé en notation pointée ; renvoie $default si elle n'existe pas. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}
