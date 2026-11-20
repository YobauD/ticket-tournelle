<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rendu des gabarits PHP du dossier app/Views.
 */
final class View
{
    /**
     * Exécute une vue et renvoie le HTML produit.
     * Les clés de $data deviennent des variables dans la vue (['ticket' => ...] donne $ticket).
     */
    public static function render(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }

    /** Vue insérée dans une mise en page (layouts/main ou layouts/auth). */
    public static function page(string $view, array $data = [], string $layout = 'main'): string
    {
        $data['content'] = self::render($view, $data);
        return self::render('layouts/' . $layout, $data);
    }

    /** Interrompt la requête avec une page d'erreur (403, 404, 500). */
    public static function abort(int $code): never
    {
        http_response_code($code);
        echo self::page('errors/error', ['code' => $code, 'title' => t('error.' . $code . '.title')], 'auth');
        exit;
    }
}
