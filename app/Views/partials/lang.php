<?php
/**
 * Sélecteur de langue. Le changement du menu envoie le formulaire (assets/js/app.js) ;
 * sans JavaScript, le bouton « OK » reste disponible.
 */
use App\Core\Lang;
?>
<form class="lang-switch" method="post" action="<?= e(url('/lang')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <label class="sr-only" for="lang-select"><?= e(t('lang.label')) ?></label>
    <select id="lang-select" name="lang" data-autosubmit>
        <?php foreach (Lang::supported() as $code): ?>
            <option value="<?= e($code) ?>" <?= $code === Lang::locale() ? 'selected' : '' ?>><?= e(t('lang.' . $code)) ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-small" type="submit">OK</button></noscript>
</form>
