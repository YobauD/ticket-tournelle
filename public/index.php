<?php
/**
 * Contrôleur frontal : unique point d'entrée de l'application.
 * Apache ne sert que le dossier public/ ; le code, la configuration et les
 * fichiers déposés restent inaccessibles depuis le navigateur.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Router;

$router = new Router(require BASE_PATH . '/config/routes.php', (string) Config::get('app.base_url'));
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
