<?php
/**
 * Administration : comptes et habilitations.
 * Variables : $users, $roles (rôles que la personne connectée peut attribuer), $allRoles (par code),
 *             $jobTitles (fonctions actives, proposées pour un compte),
 *             $requests (demandes « mot de passe oublié » que la personne connectée peut traiter),
 *             $actor (personne connectée), $old (saisie du formulaire de création), $errors.
 *
 * Une ligne n'est modifiable que si AccountPolicy::canManage() l'autorise ; sinon elle est
 * affichée en lecture seule, avec la raison. Le serveur refait de toute façon le contrôle.
 */
use App\Core\Config;
use App\Core\Lang;
use App\Core\View;
use App\Services\AccountPolicy;

$min = Config::get('security.password_min_length');

/**
 * Options du menu « Fonction » : « non renseignée », les fonctions actives, et la fonction
 * actuelle du compte même si elle a été désactivée depuis (elle reste sur le compte).
 */
$titleOptions = static function (?int $currentId, ?string $currentLabel) use ($jobTitles): string {
    $html  = '<option value="0">' . e(t('admin.no_title')) . '</option>';
    $found = false;
    foreach ($jobTitles as $title) {
        $isCurrent = (int) $title['id'] === $currentId;
        $found     = $found || $isCurrent;
        $html     .= '<option value="' . (int) $title['id'] . '"' . ($isCurrent ? ' selected' : '') . '>' . e(label($title, 'label')) . '</option>';
    }
    if ($currentId !== null && !$found) {
        $html .= '<option value="' . $currentId . '" selected>' . e((string) $currentLabel) . '</option>';
    }
    return $html;
};

/**
 * « Nouveau mot de passe provisoire », dans la liste des comptes et dans le bandeau des demandes.
 * Le serveur le tire au hasard et l'affiche une seule fois après l'envoi : l'administrateur
 * ne choisit pas (et ne réutilise pas) de mot de passe. $prefix distingue les deux emplacements.
 */
$resetForm = static function (int $uid, string $prefix, string $summaryKey): string {
    ob_start(); ?>
    <details class="popover">
        <summary class="btn btn-small <?= $prefix === 'pw' ? 'btn-quiet' : '' ?>"><?= e(t($summaryKey)) ?></summary>
        <form method="post" action="<?= e(url('/admin/users/' . $uid . '/password')) ?>">
            <?= csrf_field() ?>
            <p class="hint"><?= e(t('admin.reset_explain')) ?></p>
            <button class="btn btn-small" type="submit"><?= e(t('admin.reset_submit')) ?></button>
        </form>
    </details>
    <?php return (string) ob_get_clean();
};

$error = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error">' . e($errors[$field]) . '</p>'
    : '';
?>
<?= View::render('admin/tabs', ['current' => '/admin/users']) ?>

<div class="page-head">
    <div>
        <h1><?= e(t('nav.users')) ?></h1>
        <p class="head-meta"><?= e(t($actor['role_code'] === 'superadmin' ? 'admin.rights_superadmin' : 'admin.rights_admin')) ?></p>
    </div>
    <a class="btn btn-primary" href="#new-user"><?= e(t('admin.new_user')) ?></a>
</div>

<?php if ($requests !== []): ?>
    <!-- Demandes faites depuis la page de connexion : à traiter avant tout le reste -->
    <section class="card card-requests" id="requests" aria-labelledby="requests-title">
        <h2 id="requests-title">
            <?= e(t('pwreq.title')) ?> <span class="count count-alert"><?= count($requests) ?></span>
        </h2>
        <p class="hint"><?= e(t('pwreq.intro')) ?></p>

        <ul class="request-list">
            <?php foreach ($requests as $request): ?>
                <?php $uid = (int) $request['id']; ?>
                <li class="request">
                    <div class="request-who">
                        <strong><?= e(person($request)) ?></strong>
                        <span class="row-meta"><?= e($request['username']) ?> · <?= e($request['email'] ?? t('admin.no_email')) ?> · <?= e(user_subtitle($request)) ?></span>
                        <span class="row-meta"><?= e(t('pwreq.requested', ['date' => dt($request['request_at'])])) ?></span>
                        <?php if ($request['request_message'] !== null): ?>
                            <p class="request-message"><span class="sr-only"><?= e(t('pwreq.message')) ?> : </span><?= e($request['request_message']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="request-actions">
                        <?php if ($request['role_code'] === 'superadmin'): ?>
                            <span class="lock-note"><?= e(t('pwreq.server_only')) ?></span>
                        <?php else: ?>
                            <?= $resetForm($uid, 'pwr', 'pwreq.reset') ?>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('/admin/password-requests/' . (int) $request['request_id'] . '/dismiss')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-small btn-quiet" type="submit"><?= e(t('pwreq.dismiss')) ?></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<div class="table-wrap">
    <table class="table table-stack table-users">
        <thead>
            <tr>
                <th scope="col"><?= e(t('user.name')) ?></th>
                <th scope="col"><?= e(t('user.job_title')) ?></th>
                <th scope="col"><?= e(t('user.role')) ?></th>
                <th scope="col"><?= e(t('ticket.level')) ?></th>
                <th scope="col"><?= e(t('user.active')) ?></th>
                <th scope="col"><?= e(t('user.last_login')) ?></th>
                <th scope="col"><span class="sr-only"><?= e(t('tech.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <?php
                $uid    = (int) $user['id'];
                $formId = 'user-' . $uid;   // les champs de la ligne sont rattachés au formulaire par l'attribut form=""
                $lock   = AccountPolicy::lockReason($actor, $user);
                $role   = $allRoles[$user['role_code']];
                ?>
                <tr class="<?= $lock !== null ? 'is-readonly' : '' ?>">
                    <td data-label="<?= e(t('user.name')) ?>">
                        <strong><?= e(person($user)) ?></strong>
                        <?php if ((int) $user['is_locked'] === 1): ?><span class="badge badge-late"><?= e(t('user.locked')) ?></span><?php endif; ?>
                        <span class="row-meta"><span class="sr-only"><?= e(t('auth.username')) ?> : </span><?= e($user['username']) ?> · <?= $user['email'] !== null ? e($user['email']) : '<em>' . e(t('admin.no_email')) . '</em>' ?></span>
                    </td>

                    <?php if ($lock !== null): ?>
                        <!-- Compte non modifiable par la personne connectée : lecture seule -->
                        <td data-label="<?= e(t('user.job_title')) ?>">
                            <?= $user['job_title_id'] !== null ? e(label($user, 'job_title')) : '<span class="muted">—</span>' ?>
                        </td>
                        <td data-label="<?= e(t('user.role')) ?>">
                            <span class="tag <?= $user['role_code'] === 'superadmin' ? 'tag-strong' : '' ?>"><?= e(label($role, 'label')) ?></span>
                        </td>
                        <td data-label="<?= e(t('ticket.level')) ?>"><?= $user['tech_level'] !== null ? 'N' . (int) $user['tech_level'] : '<span class="muted">—</span>' ?></td>
                        <td data-label="<?= e(t('user.active')) ?>"><?= e(t((int) $user['is_active'] === 1 ? 'admin.yes' : 'admin.no')) ?></td>
                        <td data-label="<?= e(t('user.last_login')) ?>"><?= e(dt($user['last_login_at'])) ?></td>
                        <td class="cell-action"><span class="lock-note"><?= e(t($lock)) ?></span></td>
                    <?php else: ?>
                        <td data-label="<?= e(t('user.job_title')) ?>">
                            <label class="sr-only" for="title-<?= $uid ?>"><?= e(t('user.job_title')) ?></label>
                            <select id="title-<?= $uid ?>" name="job_title_id" form="<?= $formId ?>">
                                <?= $titleOptions($user['job_title_id'] === null ? null : (int) $user['job_title_id'], label($user, 'job_title')) ?>
                            </select>
                        </td>
                        <td data-label="<?= e(t('user.role')) ?>">
                            <label class="sr-only" for="role-<?= $uid ?>"><?= e(t('user.role')) ?></label>
                            <select id="role-<?= $uid ?>" name="role_id" form="<?= $formId ?>" data-role-select>
                                <?php foreach ($roles as $option): ?>
                                    <option value="<?= (int) $option['id'] ?>" data-code="<?= e($option['code']) ?>"
                                            <?= (int) $option['id'] === (int) $user['role_id'] ? 'selected' : '' ?>>
                                        <?= e(label($option, 'label')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="<?= e(t('ticket.level')) ?>">
                            <label class="sr-only" for="level-<?= $uid ?>"><?= e(t('ticket.level')) ?></label>
                            <select id="level-<?= $uid ?>" name="tech_level" form="<?= $formId ?>" data-level-select>
                                <?php foreach ([1, 2, 3] as $level): ?>
                                    <option value="<?= $level ?>" <?= (int) $user['tech_level'] === $level ? 'selected' : '' ?>>N<?= $level ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td data-label="<?= e(t('user.active')) ?>">
                            <label class="check">
                                <input type="checkbox" name="is_active" value="1" form="<?= $formId ?>"
                                       <?= (int) $user['is_active'] === 1 ? 'checked' : '' ?>>
                                <span class="sr-only"><?= e(t('user.active')) ?></span>
                            </label>
                        </td>
                        <td data-label="<?= e(t('user.last_login')) ?>"><?= e(dt($user['last_login_at'])) ?></td>
                        <td class="cell-action">
                            <form id="<?= $formId ?>" method="post" action="<?= e(url('/admin/users/' . $uid)) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-small" type="submit"><?= e(t('form.save')) ?></button>
                            </form>
                            <?= $resetForm($uid, 'pw', 'admin.reset_password') ?>
                            <details class="popover">
                                <summary class="btn btn-small btn-quiet"><?= e(t('admin.edit_username')) ?></summary>
                                <form method="post" action="<?= e(url('/admin/users/' . $uid . '/identity')) ?>">
                                    <?= csrf_field() ?>
                                    <label for="un-<?= $uid ?>"><?= e(t('auth.username')) ?></label>
                                    <input type="text" id="un-<?= $uid ?>" name="username" value="<?= e($user['username']) ?>"
                                           maxlength="50" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                                    <p class="hint"><?= e(t('admin.identity_hint')) ?></p>
                                    <button class="btn btn-small" type="submit"><?= e(t('form.save')) ?></button>
                                </form>
                            </details>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section class="card form narrow" id="new-user">
    <h2><?= e(t('admin.new_user')) ?></h2>
    <p class="hint"><?= e(t('admin.new_user_hint')) ?></p>

    <form method="post" action="<?= e(url('/admin/users')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="field-row">
            <div class="field <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
                <label for="first_name"><?= e(t('user.first_name')) ?></label>
                <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" maxlength="80" required>
                <?= $error('first_name') ?>
            </div>
            <div class="field <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
                <label for="last_name"><?= e(t('user.last_name')) ?></label>
                <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" maxlength="80" required>
                <?= $error('last_name') ?>
            </div>
        </div>

        <div class="field <?= isset($errors['username']) ? 'has-error' : '' ?>">
            <label for="new_username"><?= e(t('auth.username')) ?> <span class="optional"><?= e(t('form.optional')) ?></span></label>
            <input type="text" id="new_username" name="username" value="<?= e($old['username'] ?? '') ?>" maxlength="50"
                   autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="new_username_hint">
            <p class="hint" id="new_username_hint"><?= e(t('admin.username_hint')) ?></p>
            <?= $error('username') ?>
        </div>


        <div class="field <?= isset($errors['job_title_id']) ? 'has-error' : '' ?>">
            <label for="new_title"><?= e(t('user.job_title')) ?> <span class="optional"><?= e(t('form.optional')) ?></span></label>
            <select id="new_title" name="job_title_id" aria-describedby="new_title_hint">
                <?= $titleOptions(($old['job_title_id'] ?? 0) ?: null, null) ?>
            </select>
            <p class="hint" id="new_title_hint"><?= e(t('admin.title_hint')) ?></p>
            <?= $error('job_title_id') ?>
        </div>

        <div class="field-row">
            <div class="field <?= isset($errors['role_id']) ? 'has-error' : '' ?>">
                <label for="new_role"><?= e(t('user.role')) ?></label>
                <select id="new_role" name="role_id" required data-role-select>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" data-code="<?= e($role['code']) ?>"
                                <?= ($old['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                            <?= e(label($role, 'label')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $error('role_id') ?>
            </div>
            <div class="field">
                <label for="new_level"><?= e(t('ticket.level')) ?></label>
                <select id="new_level" name="tech_level" data-level-select>
                    <?php foreach ([1, 2, 3] as $level): ?>
                        <option value="<?= $level ?>" <?= ($old['tech_level'] ?? 1) === $level ? 'selected' : '' ?>>N<?= $level ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="new_lang"><?= e(t('lang.label')) ?></label>
                <select id="new_lang" name="lang">
                    <?php foreach (Lang::supported() as $code): ?>
                        <option value="<?= e($code) ?>" <?= ($old['lang'] ?? 'fr') === $code ? 'selected' : '' ?>><?= e(t('lang.' . $code)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= e(t('admin.create_user')) ?></button>
        </div>
    </form>
</section>
