<?php
/**
 * Nouveau mot de passe, ouvert depuis le lien reçu par e-mail.
 * Variables : $old['token'] (jeton du lien, vide s'il n'est plus valable), $errors.
 * Le jeton voyage dans un champ caché : il n'apparaît que dans le lien de l'e-mail.
 */
use App\Core\Config;
use App\Core\View;

$min   = Config::get('security.password_min_length');
$token = $old['token'] ?? '';
?>
<section class="card auth-card">
    <h1><?= e(t('reset.title')) ?></h1>

    <?= View::render('partials/flash') ?>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
        <p><a class="btn btn-primary btn-block" href="<?= e(url('/password/forgot')) ?>"><?= e(t('reset.new_link')) ?></a></p>
    <?php else: ?>
        <p class="lead"><?= e(t('reset.intro')) ?></p>
        <form method="post" action="<?= e(url('/password/reset')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="field <?= isset($errors['new_password']) ? 'has-error' : '' ?>">
                <label for="new_password"><?= e(t('account.new_password')) ?></label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                       minlength="<?= (int) $min ?>" required autofocus aria-describedby="new_password_hint">
                <p class="hint" id="new_password_hint"><?= e(t('password.rules', ['min' => $min])) ?></p>
                <?php if (isset($errors['new_password'])): ?><p class="error"><?= e($errors['new_password']) ?></p><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password"><?= e(t('account.confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>

            <button class="btn btn-primary btn-block" type="submit"><?= e(t('reset.submit')) ?></button>
        </form>
    <?php endif; ?>
    <p class="hint"><a href="<?= e(url('/login')) ?>"><?= e(t('forgot.back')) ?></a></p>
</section>
