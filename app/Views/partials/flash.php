<?php
/** Messages de confirmation ou d'erreur enregistrés par l'action précédente. */
use App\Core\Session;
?>
<?php foreach (Session::pullFlash() as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endforeach; ?>
