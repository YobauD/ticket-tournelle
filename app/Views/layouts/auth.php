<?php
/**
 * Mise en page des écrans hors connexion (connexion, pages d'erreur).
 */
use App\Core\Config;
use App\Core\Lang;
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::locale()) ?>"
      data-password-show="<?= e(t('password.show')) ?>" data-password-hide="<?= e(t('password.hide')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? '') ?> – <?= e(Config::get('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
</head>
<body class="auth-body">
<div class="auth-lang"><?= View::render('partials/lang') ?></div>

<main class="auth-shell">
    <a class="brand brand-large" href="<?= e(url('/')) ?>">
        <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="40" height="40">
        <span><?= e(Config::get('app.name')) ?></span>
    </a>
    <?= $content ?>
    <p class="auth-foot"><?= e(t('app.school')) ?></p>
</main>
<?= View::render('partials/chatbot') ?>
</body>
</html>
