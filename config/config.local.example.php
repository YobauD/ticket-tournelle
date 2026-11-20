<?php
/**
 * Copiez ce fichier en config.local.php et adaptez les valeurs au serveur.
 * config.local.php est ignoré par Git : c'est le seul endroit où stocker des secrets.
 */
return [
    'app' => [
        'debug'    => true,
        'full_url' => 'http://localhost:8000',
    ],
    'db' => [
        'user'     => 'tt_app',
        'password' => 'MotDePasseFortAChanger',
    ],
    'mail' => [
        'driver' => 'log',
    ],
];
