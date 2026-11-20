<?php
/**
 * Clôture automatique des tickets résolus depuis plus de N jours sans retour du déclarant
 * (N = config 'tickets.auto_close_days', 7 par défaut).
 *
 * À planifier une fois par jour avec cron, par exemple à 6 h :
 *   0 6 * * * www-data php /var/www/ticket-tournelle/scripts/auto_close.php >> /var/www/ticket-tournelle/storage/logs/cron.log 2>&1
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Models\Ticket;
use App\Services\Notifier;

if (PHP_SAPI !== 'cli') {
    exit('Ce script se lance en ligne de commande.');
}

$days   = (int) Config::get('tickets.auto_close_days');
$closed = 0;

foreach (Ticket::resolvedSince($days) as $ticket) {
    try {
        // Acteur null = le système ; l'historique gardera la trace « clôture automatique »
        Ticket::changeStatus($ticket, 'closed', null, 'auto_closed');
        Notifier::declarant(Ticket::find((int) $ticket['id']), 'auto_closed');
        $closed++;
    } catch (DomainException) {
        // Le déclarant vient de répondre : on laisse le ticket tranquille
    }
}

echo date('Y-m-d H:i:s') . " - $closed ticket(s) clôturé(s) automatiquement\n";
