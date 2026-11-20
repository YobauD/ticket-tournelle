<?php
/**
 * Configuration par défaut de l'application.
 *
 * Ne mettez AUCUN secret ici : ce fichier est versionné sur Git.
 * Les valeurs propres au serveur (mot de passe MySQL, SMTP...) se placent dans
 * config/config.local.php, qui écrase les clés ci-dessous et n'est pas versionné.
 */

$config = [
    'app' => [
        'name'     => "Ticket'Tournelle",
        'debug'    => false,          // true uniquement en développement
        // Dossier d'installation dans l'URL. null = détecté automatiquement :
        // '' si le site est à la racine du serveur, '/ticket-tournelle' s'il est dans htdocs/ticket-tournelle (MAMP)
        'base_url' => null,
        'full_url' => 'http://tickets.latournelle.local', // utilisé dans les liens des e-mails
        'timezone' => 'Europe/Paris',
    ],

    'db' => [
        'host'     => '127.0.0.1',        // 127.0.0.1 plutôt que localhost : connexion TCP, le port est respecté
        'port'     => 3306,
        'socket'   => null,               // chemin d'un socket Unix, utilisé à la place de host/port s'il est renseigné
        'name'     => 'ticket_tournelle',
        'user'     => 'tt_app',
        'password' => '',             // à définir dans config.local.php
    ],

    'lang' => [
        'default'   => 'fr',
        'supported' => ['fr', 'en'],
    ],

    'security' => [
        'session_name'        => 'TTSESSID',
        'session_timeout'     => 1800,   // déconnexion après 30 min d'inactivité
        'max_login_attempts'  => 5,      // verrouillage du compte au-delà
        'lockout_minutes'     => 15,
        'password_min_length' => 12,     // recommandation ANSSI pour un mot de passe seul
        'reset_requests_per_hour' => 5,  // demandes « mot de passe oublié » par heure depuis un même poste
    ],

    'uploads' => [
        'dir'      => dirname(__DIR__) . '/storage/uploads',
        'max_size' => 5 * 1024 * 1024,   // 5 Mo
        // Type MIME réel (détecté côté serveur) => extension imposée au stockage
        'allowed'  => [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'application/pdf' => 'pdf',
        ],
    ],

    'mail' => [
        // 'log'  : les e-mails sont écrits dans storage/logs/mail.log (développement)
        // 'mail' : envoi réel via la fonction mail() de PHP (nécessite un MTA, ex. Postfix)
        'driver'          => 'log',
        'from_address'    => 'tickets@latournelle.local',
        'from_name'       => "Ticket'Tournelle",
        'support_address' => 'support@latournelle.local', // boîte de l'équipe des référents
    ],

    'tickets' => [
        'auto_close_days' => 7,          // clôture automatique d'un ticket résolu
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
