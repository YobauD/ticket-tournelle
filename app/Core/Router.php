<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur : associe « méthode + URL » à une action de contrôleur.
 *
 * C'est aussi le point de contrôle unique de la sécurité : avant d'appeler le
 * contrôleur, il vérifie le jeton CSRF (POST), la connexion et le rôle (RBAC).
 * Une nouvelle route est donc protégée par défaut, sans code à ajouter.
 */
final class Router
{
    /** Pages accessibles même si le changement de mot de passe est imposé. */
    // Pages ouvertes pendant le changement de mot de passe imposé. L'aide en fait partie :
    // sa fiche « Première connexion » explique les règles du nouveau mot de passe.
    private const PASSWORD_CHANGE_EXEMPT = ['/account', '/logout', '/lang', '/help', '/help/ask', '/privacy'];

    public function __construct(
        private readonly array $routes,
        private readonly string $basePath = ''
    ) {
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize($uri);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach ($this->routes as [$routeMethod, $pattern, $handler, $roles]) {
            if ($routeMethod !== $method) {
                continue;
            }
            // '/tickets/{id}'  ->  '#^/tickets/(\d+)$#'
            $regex = '#^' . preg_replace('#\{[a-z]+\}#', '(\d+)', $pattern) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            array_shift($matches);

            $this->guard($method, $path, $roles);

            [$class, $action] = $handler;
            (new $class())->$action(...array_map('intval', $matches));
            return;
        }

        View::abort(404);
    }

    /** Contrôles de sécurité communs à toutes les routes. */
    private function guard(string $method, string $path, ?array $roles): void
    {
        // 1. CSRF : un POST sans jeton valide n'est jamais exécuté
        if ($method === 'POST' && !Csrf::check()) {
            Session::flash('error', t('error.csrf'));
            header('Location: ' . url('/'));
            exit;
        }

        if ($roles === null) {
            return; // route publique
        }

        // 2. Authentification
        if (!Auth::check()) {
            if (Session::get('expired')) {
                Session::forget('expired');
                Session::flash('info', t('auth.session_expired'));
            }
            header('Location: ' . url('/login'));
            exit;
        }

        // 3. Autorisation : le rôle est lu en base, jamais fourni par le navigateur
        if (!Auth::is(...$roles)) {
            View::abort(403);
        }

        // 4. Mot de passe provisoire : changement obligatoire avant toute autre action
        if ((int) Auth::user()['must_change_password'] === 1
            && !in_array($path, self::PASSWORD_CHANGE_EXEMPT, true)) {
            header('Location: ' . url('/account'));
            exit;
        }
    }

    /** Extrait le chemin de l'URL, sans paramètres ni préfixe d'installation. */
    private function normalize(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = $this->basePath;
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }
        // Adresse saisie avec .../public/ : on sert la même page que sans
        if ($path === '/public' || str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public'));
        }
        return '/' . trim($path, '/');
    }
}
