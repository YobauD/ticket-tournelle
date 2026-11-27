<?php
/**
 * Amorçage commun au site web (public/index.php) et aux scripts en ligne
 * de commande (scripts/*.php) : autoload, configuration, erreurs, session, langue.
 */
declare(strict_types=1);

use App\Core\Config;
use App\Core\Lang;
use App\Core\Session;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

// --- Prérequis ----------------------------------------------------------------
// Vérifiés avant tout le reste : un message clair plutôt qu'une erreur incompréhensible
$missing = [];
if (PHP_VERSION_ID < 80100) {
    $missing[] = 'PHP 8.1 ou plus récent (version actuelle : ' . PHP_VERSION . ')';
}
foreach (['pdo_mysql', 'mbstring'] as $extension) {
    if (!extension_loaded($extension)) {
        $missing[] = 'extension PHP ' . $extension;
    }
}
if ($missing !== []) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit("Ticket'Tournelle ne peut pas démarrer. Manquant : " . implode(', ', $missing) . ".\n"
       . "Dans MAMP : Préférences > PHP, choisissez PHP 8.2 ou plus récent, puis redémarrez les serveurs.\n");
}

// --- Autoload : App\Core\Router  ->  app/Core/Router.php --------------------
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/helpers.php';

Config::load(require BASE_PATH . '/config/config.php');

// Dossier d'installation déduit du chemin du script exécuté :
//   /index.php                         -> ''                  (serveur PHP intégré, VirtualHost sur public/)
//   /ticket-tournelle/public/index.php -> '/ticket-tournelle' (MAMP, projet dans htdocs/ticket-tournelle)
if (PHP_SAPI !== 'cli' && Config::get('app.base_url') === null) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    Config::set('app.base_url', (string) preg_replace('#/public$#', '', rtrim($scriptDir, '/')));
}
date_default_timezone_set(Config::get('app.timezone'));
mb_internal_encoding('UTF-8');

// --- Gestion des erreurs ----------------------------------------------------
// En production, aucun détail technique n'est affiché : tout part dans le journal.
error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

set_exception_handler(static function (Throwable $e): void {
    error_log(sprintf('%s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    if (Config::get('app.debug')) {
        http_response_code(500);
        echo '<pre>' . e((string) $e) . '</pre>';
        exit;
    }
    View::abort(500);
});

if (PHP_SAPI !== 'cli') {
    // --- En-têtes de sécurité HTTP -------------------------------------------
    header('X-Frame-Options: DENY');                      // anti-clickjacking
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    // CSP : uniquement nos propres fichiers, aucun script ni style en ligne.
    // Second rempart contre les XSS, en plus de l'échappement des sorties.
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; "
         . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    // Le portail n'utilise ni caméra, ni micro, ni localisation : on les interdit à toute page
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    // En HTTPS, le navigateur refusera ensuite tout accès en HTTP clair pendant un an (HSTS)
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        header('Strict-Transport-Security: max-age=31536000');
    }

    Session::start();
}

Lang::init();
