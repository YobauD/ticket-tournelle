<?php
/**
 * Formulaire de connexion.
 * Variables : $email (saisie précédente), $error (message ou null).
 */
use App\Core\Config;
use App\Core\View;
?>
<section class="card auth-card">
    <h1><?= e(t('auth.title')) ?></h1>
    <p class="lead"><?= e(t('auth.intro')) ?></p>

    <?= View::render('partials/flash') ?>
    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>"
                   autocomplete="username" required autofocus>
        </div>

        <div class="field">
            <div class="label-row">
                <label for="password"><?= e(t('auth.password')) ?></label>
                <a href="<?= e(url('/password/forgot')) ?>"><?= e(t('auth.forgot_link')) ?></a>
            </div>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= e(t('auth.submit')) ?></button>
    </form>

    <p class="hint"><?= e(t('auth.help', [
        'attempts' => Config::get('security.max_login_attempts'),
        'minutes'  => Config::get('security.lockout_minutes'),
    ])) ?></p>
    <p class="hint"><a href="<?= e(url('/help')) ?>"><?= e(t('help.open')) ?></a></p>
</section>
