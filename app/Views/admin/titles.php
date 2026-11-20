<?php
/**
 * Administration : fonctions des personnels (Professeur, Direction, Vie scolaire…).
 * Variables : $jobTitles (avec user_count), $old (saisie du formulaire d'ajout), $errors.
 */
use App\Core\View;

$error = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error">' . e($errors[$field]) . '</p>'
    : '';
?>
<?= View::render('admin/tabs', ['current' => '/admin/titles']) ?>

<div class="page-head">
    <div>
        <h1><?= e(t('nav.titles')) ?></h1>
        <p class="head-meta"><?= e(t('ref.titles_intro')) ?></p>
    </div>
    <a class="btn btn-primary" href="#new-title"><?= e(t('ref.new_title')) ?></a>
</div>

<div class="table-wrap">
    <table class="table table-stack">
        <thead>
            <tr>
                <th scope="col"><?= e(t('ref.label_fr')) ?></th>
                <th scope="col"><?= e(t('ref.label_en')) ?></th>
                <th scope="col"><?= e(t('ref.accounts')) ?></th>
                <th scope="col"><?= e(t('ref.active')) ?></th>
                <th scope="col"><span class="sr-only"><?= e(t('tech.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobTitles as $jobTitle): ?>
                <?php $cid = (int) $jobTitle['id']; $formId = 'jobtitle-' . $cid; ?>
                <tr class="<?= (int) $jobTitle['is_active'] === 1 ? '' : 'is-inactive' ?>">
                    <td data-label="<?= e(t('ref.label_fr')) ?>">
                        <label class="sr-only" for="fr-<?= $cid ?>"><?= e(t('ref.label_fr')) ?></label>
                        <input type="text" id="fr-<?= $cid ?>" name="label_fr" form="<?= $formId ?>"
                               value="<?= e($jobTitle['label_fr']) ?>" maxlength="80" required>
                    </td>
                    <td data-label="<?= e(t('ref.label_en')) ?>">
                        <label class="sr-only" for="en-<?= $cid ?>"><?= e(t('ref.label_en')) ?></label>
                        <input type="text" id="en-<?= $cid ?>" name="label_en" form="<?= $formId ?>"
                               value="<?= e($jobTitle['label_en']) ?>" maxlength="80" required lang="en">
                    </td>
                    <td data-label="<?= e(t('ref.accounts')) ?>"><?= (int) $jobTitle['user_count'] ?></td>
                    <td data-label="<?= e(t('ref.active')) ?>">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" form="<?= $formId ?>"
                                   <?= (int) $jobTitle['is_active'] === 1 ? 'checked' : '' ?>>
                            <span class="sr-only"><?= e(t('ref.active')) ?></span>
                        </label>
                        <?php if ((int) $jobTitle['is_active'] !== 1): ?><span class="tag"><?= e(t('ref.disabled')) ?></span><?php endif; ?>
                    </td>
                    <td class="cell-action">
                        <form id="<?= $formId ?>" method="post" action="<?= e(url('/admin/titles/' . $cid)) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-small" type="submit"><?= e(t('form.save')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section class="card form narrow" id="new-title">
    <h2><?= e(t('ref.new_title')) ?></h2>
    <p class="hint"><?= e(t('ref.both_languages')) ?></p>

    <form method="post" action="<?= e(url('/admin/titles')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field-row">
            <div class="field <?= isset($errors['label_fr']) ? 'has-error' : '' ?>">
                <label for="new_label_fr"><?= e(t('ref.label_fr')) ?></label>
                <input type="text" id="new_label_fr" name="label_fr" value="<?= e($old['label_fr'] ?? '') ?>" maxlength="80" required>
                <?= $error('label_fr') ?>
            </div>
            <div class="field <?= isset($errors['label_en']) ? 'has-error' : '' ?>">
                <label for="new_label_en"><?= e(t('ref.label_en')) ?></label>
                <input type="text" id="new_label_en" name="label_en" value="<?= e($old['label_en'] ?? '') ?>" maxlength="80" required lang="en">
                <?= $error('label_en') ?>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= e(t('ref.add_title')) ?></button>
        </div>
    </form>
</section>
