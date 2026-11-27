<?php
/**
 * Mon compte : informations, mot de passe et adresse e-mail.
 * À la première connexion (mot de passe provisoire), un seul formulaire : nouveau mot de passe
 * et adresse e-mail. Ensuite, deux sections : mot de passe, et adresse e-mail.
 * Variables : $errors (champ => message), $old (saisie précédente), $pending (adresse en attente
 * de confirmation, ou null).
 */
use App\Core\Auth;
use App\Core\Config;

$user      = Auth::user();
$min       = Config::get('security.password_min_length');
$firstTime = (int) $user['must_change_password'] === 1;
$hours     = (int) Config::get('security.verify_link_hours', 48);

$fieldError = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error" id="' . $field . '_error">' . e($errors[$field]) . '</p>'
    : '';
$invalid = static fn (string $field): string => isset($errors[$field])
    ? 'aria-invalid="true" aria-describedby="' . $field . '_error"'
    : '';
$emailValue = $old['email'] ?? ($user['email'] ?? $pending ?? '');
?>
<div class="page-head">
    <h1><?= e(t('account.title')) ?></h1>
</div>

<?php if ($firstTime): ?>
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
            <dt><?= e(t('auth.email')) ?></dt>
            <dd>
                <?php if ($user['email'] !== null): ?>
                    <?= e($user['email']) ?> <span class="tag tag-ok"><?= e(t('account.email_verified')) ?></span>
                <?php else: ?>
                    <span class="muted"><?= e(t('account.no_email')) ?></span>
                <?php endif; ?>
                <?php if ($pending !== null): ?>
                    <span class="row-meta"><?= e(t('account.pending', ['email' => $pending])) ?></span>
                <?php endif; ?>
            </dd>
            <dt><?= e(t('user.job_title')) ?></dt>
            <dd><?= $user['job_title_id'] !== null ? e(label($user, 'job_title')) : '<span class="muted">' . e(t('admin.no_title_short')) . '</span>' ?></dd>
            <dt><?= e(t('user.role')) ?></dt>
            <dd><?= e(label($user, 'role')) ?></dd>
        </dl>
    </section>

    <section class="card">
        <h2><?= e(t($firstTime ? 'account.first_time' : 'account.change_password')) ?></h2>
        <form method="post" action="<?= e(url('/account')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="field <?= isset($errors['current_password']) ? 'has-error' : '' ?>">
                <label for="current_password"><?= e(t($firstTime ? 'account.temp_password' : 'account.current_password')) ?></label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required <?= $invalid('current_password') ?>>
                <?= $fieldError('current_password') ?>
            </div>

            <div class="field <?= isset($errors['new_password']) ? 'has-error' : '' ?>">
                <label for="new_password"><?= e(t('account.new_password')) ?></label>
                <input type="password" id="new_password" name="new_password" autocomplete="new-password"
                       minlength="<?= (int) $min ?>" required aria-describedby="new_password_hint">
                <p class="hint" id="new_password_hint"><?= e(t('password.rules', ['min' => $min])) ?></p>
                <?= $fieldError('new_password') ?>
            </div>

            <div class="field <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password"><?= e(t('account.confirm_password')) ?></label>
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required <?= $invalid('confirm_password') ?>>
                <?= $fieldError('confirm_password') ?>
            </div>

            <?php if ($firstTime): ?>
                <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email"><?= e(t('auth.email')) ?></label>
                    <input type="email" id="email" name="email" value="<?= e($emailValue) ?>" maxlength="190"
                           autocomplete="email" required aria-describedby="email_hint<?= isset($errors['email']) ? ' email_error' : '' ?>">
                    <p class="hint" id="email_hint"><?= e(t('account.email_hint', ['hours' => $hours])) ?>
                        <a href="<?= e(url('/privacy')) ?>"><?= e(t('privacy.more')) ?></a></p>
                    <?= $fieldError('email') ?>
                </div>
            <?php endif; ?>

            <button class="btn btn-primary" type="submit"><?= e(t($firstTime ? 'account.save_first_time' : 'account.save_password')) ?></button>
        </form>
    </section>

    <?php if (!$firstTime): ?>
        <section class="card" id="email">
            <h2><?= e(t('account.email_title')) ?></h2>
            <p class="hint"><?= e(t('account.email_hint', ['hours' => $hours])) ?>
                <a href="<?= e(url('/privacy')) ?>"><?= e(t('privacy.more')) ?></a></p>

            <?php if ($pending !== null): ?>
                <div class="alert alert-info" role="status">
                    <?= e(t('account.pending_long', ['email' => $pending])) ?>
                    <form method="post" action="<?= e(url('/account/email/resend')) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <button class="btn btn-small" type="submit"><?= e(t('account.resend')) ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/account/email')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email"><?= e(t($user['email'] !== null ? 'account.new_email' : 'auth.email')) ?></label>
                    <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="190"
                           autocomplete="email" required <?= $invalid('email') ?>>
                    <?= $fieldError('email') ?>
                </div>
                <div class="field <?= isset($errors['email_password']) ? 'has-error' : '' ?>">
                    <label for="email_password"><?= e(t('account.current_password')) ?></label>
                    <input type="password" id="email_password" name="email_password" autocomplete="current-password" required <?= $invalid('email_password') ?>>
                    <?= $fieldError('email_password') ?>
                </div>
                <button class="btn" type="submit"><?= e(t('account.send_confirmation')) ?></button>
            </form>
        </section>
    <?php endif; ?>
</div>
