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
        // Échecs de connexion, comptés sur les lockout_minutes dernières minutes :
        'max_login_attempts'  => 5,      // pour un compte depuis un même poste : ce poste est bloqué pour ce compte
        'ip_max_failures'     => 20,     // depuis un même poste, tous comptes confondus : ce poste est bloqué
        'account_max_failures' => 50,    // sur un compte, tous postes confondus : le compte est bloqué
        'lockout_minutes'     => 15,
        'password_min_length' => 12,     // recommandation ANSSI pour un mot de passe seul
        'reset_requests_per_hour' => 5,  // demandes « mot de passe oublié » par heure depuis un même poste
        'reset_link_minutes'   => 60,    // validité d'un lien « nouveau mot de passe »
        'reset_links_per_hour' => 3,     // liens envoyés au plus par heure pour un même compte
        'verify_link_hours'    => 48,    // validité d'un lien de confirmation d'adresse
        // Domaines acceptés pour l'adresse e-mail choisie par la personne, par exemple ['ac-versailles.fr'].
        // Vide : toute adresse. Restreindre garde les e-mails dans l'établissement (§2.3.1).
        'email_domains'        => [],
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

    // Durées de conservation des données personnelles (RGPD, article 5.1.e).
    // Appliquées chaque nuit par scripts/purge.php et affichées sur la page « Données personnelles ».
    // Valeurs proposées d'après les recommandations de la CNIL ; à faire valider par le chef d'établissement.
    'retention' => [
        'connection_logs_months'   => 12,  // journal : connexions, mots de passe, refus d'accès (avec l'adresse IP)
        'audit_logs_months'        => 36,  // journal : actions d'administration (§2.3.2) ; leur adresse IP est
                                           // effacée dès connection_logs_months
        'closed_tickets_months'    => 36,  // tickets clôturés ou rejetés, avec échanges, historique et pièces jointes
        'disabled_accounts_months' => 12,  // comptes désactivés : nom, identifiant et adresse e-mail effacés ensuite
        'password_requests_months' => 3,   // demandes de mot de passe traitées ou ignorées
        'email_tokens_days'        => 7,   // liens envoyés par e-mail, une fois utilisés ou expirés
        'mail_log_days'            => 30,  // copies d'e-mails du pilote 'log' (développement)
    ],

    // Page « Données personnelles » (information des personnes, RGPD article 13)
    'privacy' => [
        'controller' => 'Lycée des Métiers La Tournelle, 87 boulevard National, 92250 La Garenne-Colombes',
        // Adresse du délégué à la protection des données (DPD) de l'académie, à renseigner.
        // Vide : la page renvoie vers la direction du lycée.
        'dpo_contact' => '',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
