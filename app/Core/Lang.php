<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Gestion du bilinguisme FR/EN (contrainte C2).
 *
 * Les textes sont dans app/Lang/fr.php et app/Lang/en.php (tableaux associatifs clé => texte).
 * Ordre de choix de la langue : choix explicite (session) > langue du navigateur > langue par défaut.
 * Ajouter une langue = ajouter un fichier + son code dans config 'lang.supported'.
 */
final class Lang
{
    private static string $locale = 'fr';

    /** Dictionnaires déjà chargés : ['fr' => [...], 'en' => [...]] */
    private static array $dictionaries = [];

    public static function init(): void
    {
        self::$locale = Config::get('lang.default');
        if (PHP_SAPI === 'cli') {
            return;
        }

        $chosen  = Session::get('lang');
        $browser = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2));

        if (self::isSupported($chosen)) {
            self::$locale = $chosen;
        } elseif (self::isSupported($browser)) {
            self::$locale = $browser;
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function supported(): array
    {
        return Config::get('lang.supported');
    }

    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::supported(), true);
    }

    /** Change la langue courante et la mémorise en session. */
    public static function set(string $locale): void
    {
        if (!self::isSupported($locale)) {
            return;
        }
        self::$locale = $locale;
        if (PHP_SAPI !== 'cli') {
            Session::set('lang', $locale);
        }
    }

    /**
     * Traduit une clé.
     *
     * @param array       $params Valeurs injectées dans le texte : ':name' est remplacé par $params['name']
     * @param string|null $locale Force une langue (e-mail rédigé dans la langue du destinataire)
     */
    public static function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = self::isSupported($locale) ? $locale : self::$locale;

        // Clé absente : on tente la langue par défaut, puis on affiche la clé (repérable en test)
        $text = self::dictionary($locale)[$key]
            ?? self::dictionary(Config::get('lang.default'))[$key]
            ?? $key;

        // Remplacement en une seule passe avec strtr() : il essaie toujours l'emplacement le
        // plus long d'abord, donc « :total » n'est pas abîmé par le remplacement de « :to »
        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements[':' . $name] = (string) $value;
        }
        return strtr($text, $replacements);
    }

    private static function dictionary(string $locale): array
    {
        if (!isset(self::$dictionaries[$locale])) {
            self::$dictionaries[$locale] = require BASE_PATH . '/app/Lang/' . $locale . '.php';
        }
        return self::$dictionaries[$locale];
    }
}
