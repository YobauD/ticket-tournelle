<?php
/**
 * Purge des données personnelles arrivées au bout de leur durée de conservation (RGPD).
 * Les durées se règlent dans config/config.php, section 'retention' :
 *   - journal : connexions 12 mois, actions d'administration 36 mois (adresse IP effacée à 12 mois) ;
 *   - tickets clôturés ou rejetés : 36 mois, avec échanges, historique et pièces jointes ;
 *   - comptes désactivés : anonymisés après 12 mois ;
 *   - demandes de mot de passe traitées : 3 mois ; liens e-mail utilisés ou expirés : 7 jours ;
 *   - fichiers déposés sans ticket ; e-mails de mail.log (pilote 'log') : 30 jours.
 *
 * Usage :
 *   php scripts/purge.php --simulation   affiche ce qui serait supprimé, sans rien modifier
 *   php scripts/purge.php                applique la purge
 *
 * À planifier chaque nuit avec cron, par exemple à 3 h :
 *   0 3 * * * www-data php /var/www/ticket-tournelle/scripts/purge.php >> /var/www/ticket-tournelle/storage/logs/cron.log 2>&1
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\DataRetention;

if (PHP_SAPI !== 'cli') {
    exit('Ce script se lance en ligne de commande.');
}

$apply = !in_array('--simulation', $argv, true);

$labels = [
    'connection_logs'   => 'lignes du journal de connexion supprimées',
    'audit_logs'        => "lignes du journal d'administration supprimées",
    'audit_ips'         => 'adresses IP effacées du journal',
    'email_tokens'      => 'liens e-mail périmés supprimés',
    'password_requests' => 'demandes de mot de passe traitées supprimées',
    'closed_tickets'    => 'tickets clôturés supprimés (avec échanges et pièces jointes)',
    'disabled_accounts' => 'comptes désactivés anonymisés',
    'orphan_files'      => 'fichiers déposés sans ticket supprimés',
    'mail_log'          => 'e-mails retirés de mail.log',
];

$result = DataRetention::run($apply);

echo date('Y-m-d H:i:s') . ' - purge RGPD' . ($apply ? '' : ' (SIMULATION : rien n\'est modifié)') . "\n";
foreach ($result as $rule => $count) {
    printf("  %6d %s\n", $count, $labels[$rule]);
}
