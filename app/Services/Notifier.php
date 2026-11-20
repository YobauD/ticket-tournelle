<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Lang;
use App\Core\Mailer;
use App\Core\View;
use App\Models\User;
use Throwable;

/**
 * Notifications e-mail liées aux tickets et aux demandes de mot de passe.
 *
 * Chaque message est rédigé dans la langue du DESTINATAIRE (colonne users.lang),
 * et non dans celle de la personne qui déclenche l'action (contrainte C2).
 * Textes : clés 'mail.<événement>.subject' et 'mail.<événement>.intro' des fichiers de langue.
 */
final class Notifier
{
    /** Au déclarant. Événements : created, taken, status, comment, auto_closed */
    public static function declarant(array $ticket, string $event, ?string $message = null): void
    {
        self::send($ticket['user_email'], $ticket['user_lang'], $event, $ticket, $message);
    }

    /** Au technicien en charge, s'il y en a un. Événements : reply, reopened, confirmed */
    public static function technician(array $ticket, string $event, ?string $message = null): void
    {
        if ($ticket['tech_email'] !== null) {
            self::send($ticket['tech_email'], $ticket['tech_lang'], $event, $ticket, $message);
        }
    }

    /** À la boîte partagée de l'équipe support, à chaque nouveau ticket. */
    public static function support(array $ticket): void
    {
        self::send(Config::get('mail.support_address'), Config::get('lang.default'), 'support_new', $ticket, null);
    }

    /**
     * Demande de nouveau mot de passe faite depuis la page de connexion : prévient chaque
     * administrateur qui peut la traiter (AccountPolicy::seesResetRequest).
     * Le message ne contient aucun mot de passe ni lien de réinitialisation.
     * @param array $target compte concerné (colonnes de User::find)
     * @return int nombre d'administrateurs prévenus
     */
    public static function passwordRequest(array $target, ?string $message): int
    {
        $sent = 0;
        foreach (User::activeAdmins() as $admin) {
            if (!AccountPolicy::seesResetRequest($admin, $target)) {
                continue;
            }
            try {
                $locale = $admin['lang'];
                $params = ['name' => person($target)];
                $html   = View::render('emails/password_request', [
                    'locale'     => $locale,
                    'target'     => $target,
                    'message'    => $message,
                    'serverOnly' => $target['role_code'] === 'superadmin',
                    'link'       => full_url('/admin/users#requests'),
                ]);
                Mailer::send($admin['email'], Lang::get('mail.pwreq.subject', $params, $locale), $html);
                $sent++;
            } catch (Throwable $e) {
                error_log('Notification non envoyée : ' . $e->getMessage());
            }
        }
        return $sent;
    }

    private static function send(string $to, string $locale, string $event, array $ticket, ?string $message): void
    {
        try {
            $params = [
                'ref'    => $ticket['reference'],
                'title'  => $ticket['title'],
                'status' => label($ticket, 'status', $locale),
                'tech'   => person($ticket, 'tech_'),
                'author' => person($ticket, 'user_'),
                'days'   => Config::get('tickets.auto_close_days'),
            ];

            $html = View::render('emails/ticket', [
                'locale'  => $locale,
                'intro'   => Lang::get("mail.$event.intro", $params, $locale),
                'ticket'  => $ticket,
                'message' => $message,
                'link'    => full_url('/tickets/' . $ticket['id']),
            ]);

            Mailer::send($to, Lang::get("mail.$event.subject", $params, $locale), $html);
        } catch (Throwable $e) {
            // Un e-mail qui ne part pas ne doit jamais faire échouer l'action de l'utilisateur
            error_log('Notification non envoyée : ' . $e->getMessage());
        }
    }
}
