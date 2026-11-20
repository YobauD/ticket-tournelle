<?php
/**
 * Mot de passe oublié : la demande est transmise aux administrateurs, qui remettent un
 * mot de passe provisoire après avoir vérifié l'identité. Aucun mot de passe par e-mail.
 * Variables : $old (saisie précédente), $errors (par champ, et 'form' pour la limite d'envoi).
 */
use App\Core\View;
use App\Models\PasswordRequest;

$max   = PasswordRequest::MESSAGE_MAX;
$error = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error" id="' . $field . '_error">' . e($errors[$field]) . '</p>'
    : '';
?>
<section class="card auth-card">
    <h1><?= e(t('forgot.title')) ?></h1>
    <p class="lead"><?= e(t('forgot.intro')) ?></p>

    <?= View::render('partials/flash') ?>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/password/forgot')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="190"
                   autocomplete="username" required autofocus
                   <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email_error"' : '' ?>>
            <?= $error('email') ?>
        </div>

        <div class="field <?= isset($errors['message']) ? 'has-error' : '' ?>">
            <label for="message"><?= e(t('forgot.message')) ?> <span class="optional"><?= e(t('form.optional')) ?></span></label>
            <textarea id="message" name="message" rows="3" maxlength="<?= $max ?>"
                      aria-describedby="message_hint" data-counter="message_count"><?= e($old['message'] ?? '') ?></textarea>
            <p class="hint hint-split">
                <span id="message_hint"><?= e(t('forgot.message_hint')) ?></span>
                <span class="counter" id="message_count"></span>
            </p>
            <?= $error('message') ?>
        </div>

        <button class="btn btn-primary btn-block" type="submit"><?= e(t('forgot.submit')) ?></button>
    </form>

    <p class="hint"><?= e(t('forgot.no_email')) ?></p>
    <p class="hint"><a href="<?= e(url('/login')) ?>"><?= e(t('forgot.back')) ?></a></p>
</section>
