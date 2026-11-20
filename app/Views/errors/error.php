<?php
/** Page d'erreur générique. Variable : $code (403, 404 ou 500). */
?>
<section class="card auth-card">
    <p class="error-code"><?= (int) $code ?></p>
    <h1><?= e(t('error.' . $code . '.title')) ?></h1>
    <p class="lead"><?= e(t('error.' . $code . '.text')) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= e(t('error.back')) ?></a>
</section>
