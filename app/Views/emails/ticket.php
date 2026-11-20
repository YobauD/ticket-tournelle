<?php
/**
 * Gabarit unique des e-mails de notification, rendu dans la langue du destinataire.
 * Variables : $locale, $intro, $ticket, $message (texte libre ou null), $link.
 *
 * Les styles sont en ligne : les messageries ignorent les feuilles de style externes.
 */
use App\Core\Config;
use App\Core\Lang;

$tr = static fn (string $key): string => Lang::get($key, [], $locale);
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<body style="margin:0;padding:24px;background:#F4F6F7;font-family:Arial,Helvetica,sans-serif;color:#16282E;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #DDE4E7;border-radius:8px;">
    <tr>
        <td style="padding:16px 24px;background:#0B5563;border-radius:8px 8px 0 0;color:#FFFFFF;font-size:16px;font-weight:bold;">
            <?= e(Config::get('app.name')) ?>
        </td>
    </tr>
    <tr>
        <td style="padding:24px;font-size:15px;line-height:1.5;">
            <p style="margin:0 0 16px;"><?= e($intro) ?></p>

            <?php if ($message !== null && $message !== ''): ?>
                <p style="margin:0 0 16px;padding:12px 16px;background:#F4F6F7;border-left:3px solid #0B5563;"><?= nl2br(e($message)) ?></p>
            <?php endif; ?>

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;font-size:14px;">
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('ticket.reference')) ?></td><td><strong><?= e($ticket['reference']) ?></strong></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('ticket.subject')) ?></td><td><?= e($ticket['title']) ?></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('ticket.status')) ?></td><td><?= e(label($ticket, 'status', $locale)) ?></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('ticket.priority')) ?></td><td><?= e(label($ticket, 'priority', $locale)) ?></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('ticket.location')) ?></td><td><?= e($ticket['location_name']) ?></td></tr>
            </table>

            <p style="margin:0;">
                <a href="<?= e($link) ?>" style="display:inline-block;padding:10px 18px;background:#0B5563;color:#FFFFFF;text-decoration:none;border-radius:6px;font-weight:bold;"><?= e($tr('mail.open_ticket')) ?></a>
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 24px;border-top:1px solid #DDE4E7;font-size:12px;color:#5C6F76;"><?= e($tr('mail.footer')) ?></td>
    </tr>
</table>
</body>
</html>
