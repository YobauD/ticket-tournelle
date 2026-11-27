<?php
/**
 * Secours du mot de passe oublié, pour qui n'a plus accès à sa messagerie : la demande est
 * transmise aux administrateurs, qui remettent un mot de passe provisoire après avoir vérifié
 * l'identité. Aucun mot de passe par e-mail.
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
    <h1><?= e(t('pwadmin.title')) ?></h1>
    <p class="lead"><?= e(t('pwadmin.intro')) ?></p>

    <?= View::render('partials/flash') ?>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/password/admin')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field <?= isset($errors['identifier']) ? 'has-error' : '' ?>">
            <label for="identifier"><?= e(t('forgot.identifier')) ?></label>
            <input type="text" id="identifier" name="identifier" value="<?= e($old['identifier'] ?? '') ?>" maxlength="190"
                   autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus
                   aria-describedby="identifier_hint<?= isset($errors['identifier']) ? ' identifier_error' : '' ?>"
                   <?= isset($errors['identifier']) ? 'aria-invalid="true"' : '' ?>>
            <p class="hint" id="identifier_hint"><?= e(t('forgot.identifier_hint')) ?></p>
            <?= $error('identifier') ?>
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

    <p class="hint"><a href="<?= e(url('/login')) ?>"><?= e(t('forgot.back')) ?></a></p>
</section>
