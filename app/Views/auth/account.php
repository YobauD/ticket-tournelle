<?php
/**
 * Mon compte : informations et changement de mot de passe.
 * Variable : $errors (champ => message).
 */
use App\Core\Auth;
use App\Core\Config;

$user = Auth::user();
$min  = Config::get('security.password_min_length');
?>
<div class="page-head">
    <h1><?= e(t('account.title')) ?></h1>
</div>

<?php if ((int) $user['must_change_password'] === 1): ?>
    <div class="alert alert-info" role="status"><?= e(t('account.must_change')) ?></div>
<?php endif; ?>

<div class="narrow">
    <section class="card">
        <h2><?= e(t('account.profile')) ?></h2>
        <dl class="details">
            <dt><?= e(t('user.name')) ?></dt>
            <dd><?= e(person($user)) ?></dd>
            <dt><?= e(t('auth.username')) ?></dt>
            <dd><code><?= e($user['username']) ?></code></dd>
            <dt><?= e(t('account.notify_email')) ?></dt>
            <dd><?= e($user['email']) ?></dd>
            <dt><?= e(t('user.job_title')) ?></dt>
            <dd><?= $user['job_title_id'] !== null ? e(label($user, 'job_title')) : '<span class="muted">' . e(t('admin.no_title_short')) . '</span>' ?></dd>
            <dt><?= e(t('user.role')) ?></dt>
            <dd><?= e(label($user, 'role')) ?></dd>
        </dl>
    </section>

    <section class="card">
        <h2><?= e(t('account.change_password')) ?></h2>
        <form method="post" action="<?= e(url('/account')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="field <?= isset($errors['current_password']) ? 'has-error' : '' ?>">
                <label for="current_password"><?= e(t('account.current_password')) ?></label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                <?php if (isset($errors['current_password'])): ?><p class="error"><?= e($errors['current_password']) ?></p><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['new_password']) ? 'has-error' : '' ?>">
                <label for="new_password"><?= e(t('account.new_password')) ?></label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                       minlength="<?= (int) $min ?>" required aria-describedby="new_password_hint">
                <p class="hint" id="new_password_hint"><?= e(t('password.rules', ['min' => $min])) ?></p>
                <?php if (isset($errors['new_password'])): ?><p class="error"><?= e($errors['new_password']) ?></p><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password"><?= e(t('account.confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?><p class="error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>

            <button class="btn btn-primary" type="submit"><?= e(t('account.save_password')) ?></button>
        </form>
    </section>
</div>
