<?php
/**
 * E-mail lié au compte : lien « nouveau mot de passe », confirmation de l'adresse de
 * récupération, avis de changement de mot de passe. Rendu dans la langue du destinataire.
 * Variables : $locale, $kind (reset_link, verify_link, password_changed), $user, $link (ou null),
 *             $params (remplacements des textes).
 */
use App\Core\Config;
use App\Core\Lang;

$tr = static fn (string $key): string => Lang::get('mail.' . $kind . '.' . $key, $params, $locale);
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
            <p style="margin:0 0 16px;"><?= e(Lang::get('mail.hello', ['name' => $user['first_name']], $locale)) ?></p>
            <p style="margin:0 0 20px;"><?= e($tr('intro')) ?></p>
            <?php if ($link !== null): ?>
                <p style="margin:0 0 20px;">
                    <a href="<?= e($link) ?>" style="display:inline-block;padding:10px 18px;background:#0B5563;color:#FFFFFF;text-decoration:none;border-radius:6px;font-weight:bold;"><?= e($tr('button')) ?></a>
                </p>
                <p style="margin:0 0 16px;font-size:13px;color:#55686F;"><?= e(Lang::get('mail.link_fallback', [], $locale)) ?><br>
                    <span style="word-break:break-all;"><?= e($link) ?></span></p>
            <?php endif; ?>
            <p style="margin:0;padding:12px 16px;background:#FFF0D1;border-left:3px solid #7D4A00;font-size:14px;"><?= e($tr('note')) ?></p>
        </td>
    </tr>
    <tr>
        <td style="padding:14px 24px;border-top:1px solid #DDE4E7;font-size:12px;color:#55686F;"><?= e(Lang::get('mail.footer', [], $locale)) ?></td>
    </tr>
</table>
</body>
</html>
