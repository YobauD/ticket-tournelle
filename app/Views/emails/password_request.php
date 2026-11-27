<?php
/**
 * E-mail aux administrateurs : demande de nouveau mot de passe faite depuis la page de connexion.
 * Rendu dans la langue du destinataire. Variables : $locale, $target (compte concerné),
 * $message (texte libre ou null), $serverOnly (compte superadministrateur), $link.
 *
 * Aucun mot de passe ni lien de réinitialisation : l'administrateur vérifie l'identité de
 * la personne, puis lui remet un mot de passe provisoire depuis Administration > Utilisateurs.
 */
use App\Core\Config;
use App\Core\Lang;

$tr = static fn (string $key, array $params = []): string => Lang::get($key, $params, $locale);
$jobTitle = label($target, 'job_title', $locale);
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
            <p style="margin:0 0 16px;"><?= e($tr('mail.pwreq.intro', ['name' => person($target)])) ?></p>

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;font-size:14px;">
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('user.name')) ?></td><td><strong><?= e(person($target)) ?></strong></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('auth.username')) ?></td><td><?= e($target['username']) ?></td></tr>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('auth.email')) ?></td><td><?= e($target['email']) ?></td></tr>
                <?php if ($jobTitle !== ''): ?>
                    <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('user.job_title')) ?></td><td><?= e($jobTitle) ?></td></tr>
                <?php endif; ?>
                <tr><td style="padding:3px 16px 3px 0;color:#5C6F76;"><?= e($tr('user.role')) ?></td><td><?= e(label($target, 'role', $locale)) ?></td></tr>
            </table>

            <?php if ($message !== null && $message !== ''): ?>
                <p style="margin:0 0 4px;color:#5C6F76;font-size:14px;"><?= e($tr('pwreq.message')) ?></p>
                <p style="margin:0 0 16px;padding:12px 16px;background:#F4F6F7;border-left:3px solid #0B5563;"><?= nl2br(e($message)) ?></p>
            <?php endif; ?>

            <p style="margin:0 0 20px;padding:12px 16px;background:#FFF4E5;border-left:3px solid #B25E09;font-size:14px;">
                <?= e($tr($serverOnly ? 'mail.pwreq.server' : 'mail.pwreq.check')) ?>
            </p>

            <p style="margin:0;">
                <a href="<?= e($link) ?>" style="display:inline-block;padding:10px 18px;background:#0B5563;color:#FFFFFF;text-decoration:none;border-radius:6px;font-weight:bold;"><?= e($tr('mail.pwreq.open')) ?></a>
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 24px;border-top:1px solid #DDE4E7;font-size:12px;color:#5C6F76;"><?= e($tr('mail.footer')) ?></td>
    </tr>
</table>
</body>
</html>
