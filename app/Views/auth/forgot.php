<?php
/**
 * Mot de passe oublié : la personne saisit son identifiant (ou son adresse e-mail) et reçoit
 * un lien pour choisir un nouveau mot de passe à son adresse confirmée.
 * Variables : $old (saisie précédente), $errors (par champ, et 'form' pour la limite d'envoi).
 */
use App\Core\Config;
use App\Core\View;
?>
<section class="card auth-card">
    <h1><?= e(t('forgot.title')) ?></h1>
    <p class="lead"><?= e(t('forgot.intro', ['minutes' => (int) Config::get('security.reset_link_minutes', 60)])) ?></p>

    <?= View::render('partials/flash') ?>
    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/password/forgot')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field <?= isset($errors['identifier']) ? 'has-error' : '' ?>">
            <label for="identifier"><?= e(t('forgot.identifier')) ?></label>
            <input type="text" id="identifier" name="identifier" value="<?= e($old['identifier'] ?? '') ?>" maxlength="190"
                   autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus
                   aria-describedby="identifier_hint<?= isset($errors['identifier']) ? ' identifier_error' : '' ?>"
                   <?= isset($errors['identifier']) ? 'aria-invalid="true"' : '' ?>>
            <p class="hint" id="identifier_hint"><?= e(t('forgot.identifier_hint')) ?></p>
            <?php if (isset($errors['identifier'])): ?><p class="error" id="identifier_error"><?= e($errors['identifier']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit"><?= e(t('forgot.submit')) ?></button>
    </form>

    <p class="hint"><?= e(t('forgot.no_access')) ?> <a href="<?= e(url('/password/admin')) ?>"><?= e(t('forgot.ask_admin')) ?></a></p>
    <p class="hint"><a href="<?= e(url('/login')) ?>"><?= e(t('forgot.back')) ?></a></p>
</section>
