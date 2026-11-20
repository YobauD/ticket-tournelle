<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Envoi des e-mails de notification.
 *
 * Pilote 'log'  : écrit le message dans storage/logs/mail.log (développement, recette).
 * Pilote 'mail' : envoi réel par la fonction mail() de PHP via le MTA du serveur (Postfix).
 * Pour passer à un SMTP authentifié (PHPMailer), seule la méthode send() est à adapter.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        // Une adresse invalide ou contenant un saut de ligne est rejetée (injection d'en-têtes)
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        $subject = trim(preg_replace('/[\r\n]+/', ' ', $subject));

        if (Config::get('mail.driver') === 'mail') {
            $from = sprintf(
                '%s <%s>',
                mb_encode_mimeheader(Config::get('mail.from_name'), 'UTF-8'),
                Config::get('mail.from_address')
            );
            $headers = [
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/html; charset=UTF-8',
                'From'         => $from,
            ];
            return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $html, $headers);
        }

        // Version texte du message : balises retirées, lignes vides et indentation supprimées
        $text  = html_entity_decode(strip_tags(str_replace('</td><td', '</td> : <td', $html)), ENT_QUOTES, 'UTF-8');
        $text  = trim(preg_replace('/\s*\n\s*/', "\n", $text));
        $entry = sprintf("==== %s ====\nTo: %s\nSubject: %s\n\n%s\n\n", date('Y-m-d H:i:s'), $to, $subject, $text);
        return file_put_contents(BASE_PATH . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
