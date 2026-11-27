<?php
/**
 * Confirmation de l'adresse e-mail, ouverte depuis le lien reçu : un seul bouton.
 * Variables : $token (vide si le lien n'est plus valable), $email, $name.
 */
use App\Core\Auth;
use App\Core\View;
?>
<section class="card auth-card">
    <h1><?= e(t('confirm.title')) ?></h1>
    <?= View::render('partials/flash') ?>

    <?php if ($token === ''): ?>
        <div class="alert alert-error" role="alert"><?= e(t('account.link_invalid')) ?></div>
        <p><a class="btn btn-primary btn-block" href="<?= e(url(Auth::check() ? '/account#email' : '/login')) ?>">
            <?= e(t(Auth::check() ? 'account.title' : 'forgot.back')) ?></a></p>
    <?php else: ?>
        <p class="lead"><?= e(t('confirm.intro', ['name' => $name, 'email' => $email])) ?></p>
        <form method="post" action="<?= e(url('/account/email/confirm')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <button class="btn btn-primary btn-block" type="submit" autofocus><?= e(t('confirm.submit')) ?></button>
        </form>
        <p class="hint"><?= e(t('confirm.not_me')) ?></p>
    <?php endif; ?>
</section>
