<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Classe mère des contrôleurs : rendu des vues, redirections, lecture des entrées.
 */
abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        echo View::page($view, $data, $layout);
    }

    /** Réponse JSON (appels faits par le JavaScript de la page, comme l'assistant d'aide). */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    /** Champ de formulaire POST, nettoyé de ses espaces. */
    protected function input(string $key, string $default = ''): string
    {
        return $this->clean($_POST[$key] ?? $default, $default);
    }

    /** Paramètre d'URL (?status=new). */
    protected function query(string $key, string $default = ''): string
    {
        return $this->clean($_GET[$key] ?? $default, $default);
    }

    /**
     * Un navigateur envoie toujours du texte UTF-8. Un tableau (champ[]=…) ou des octets
     * invalides ne peuvent venir que d'une requête fabriquée : la valeur est ignorée,
     * au lieu de provoquer une erreur MySQL.
     */
    private function clean(mixed $value, string $default): string
    {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            return $default;
        }
        return trim($value);
    }
}
