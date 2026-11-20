<?php
/**
 * Formulaire de déclaration d'un incident.
 * Variables : $categories, $locations (lieux validés, proposés pendant la saisie), $priorities,
 *             $old (saisie à réafficher), $errors (champ => message).
 */
use App\Core\Config;

$maxSize     = (int) Config::get('uploads.max_size');
$oldPriority = $old['priority_id'] ?? null;

/** Affiche le message d'erreur d'un champ, s'il y en a un. */
$error = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error" id="' . $field . '_error">' . e($errors[$field]) . '</p>'
    : '';
?>
<div class="page-head">
    <div>
        <h1><?= e(t('ticket.new')) ?></h1>
        <p class="head-meta"><?= e(t('ticket.help_hint')) ?> <a href="<?= e(url('/help')) ?>"><?= e(t('help.open')) ?></a></p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?= e(t('form.has_errors')) ?></div>
<?php endif; ?>

<form class="card form narrow" method="post" action="<?= e(url('/tickets')) ?>" enctype="multipart/form-data" novalidate data-ticket-form>
    <?= csrf_field() ?>

    <div class="field <?= isset($errors['title']) ? 'has-error' : '' ?>">
        <label for="title"><?= e(t('ticket.title')) ?></label>
        <input type="text" id="title" name="title" value="<?= e($old['title'] ?? '') ?>"
               maxlength="150" required aria-describedby="title_hint">
        <p class="hint" id="title_hint"><?= e(t('ticket.title_hint')) ?></p>
        <?= $error('title') ?>
    </div>

    <div class="field <?= isset($errors['category_id']) ? 'has-error' : '' ?>">
        <label for="category_id"><?= e(t('ticket.category')) ?></label>
        <select id="category_id" name="category_id" required>
            <option value=""><?= e(t('form.choose')) ?></option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= ($old['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= e(label($category, 'label')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?= $error('category_id') ?>
    </div>

    <!-- Lieu : saisie libre. La liste <datalist> propose les lieux connus pendant la frappe,
         sans JavaScript, sur ordinateur comme sur téléphone. Un lieu inconnu est accepté. -->
    <div class="field <?= isset($errors['location']) ? 'has-error' : '' ?>">
        <label for="location"><?= e(t('ticket.location_input')) ?></label>
        <input type="text" id="location" name="location" value="<?= e($old['location'] ?? '') ?>"
               list="location-list" maxlength="80" autocomplete="off" required aria-describedby="location_hint">
        <datalist id="location-list">
            <?php foreach ($locations as $location): ?>
                <option value="<?= e($location['name']) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <p class="hint" id="location_hint"><?= e(t('ticket.location_hint')) ?></p>
        <?= $error('location') ?>
    </div>

    <fieldset class="field <?= isset($errors['priority_id']) ? 'has-error' : '' ?>">
        <legend><?= e(t('ticket.priority')) ?></legend>
        <div class="choices">
            <?php foreach ($priorities as $priority): ?>
                <?php $checked = $oldPriority === (int) $priority['id'] || ($oldPriority === null && $priority['code'] === 'medium'); ?>
                <label class="choice">
                    <input type="radio" name="priority_id" value="<?= (int) $priority['id'] ?>" <?= $checked ? 'checked' : '' ?> required>
                    <span class="choice-body">
                        <?= priority_badge(['priority_code' => $priority['code'], 'priority_fr' => $priority['label_fr'], 'priority_en' => $priority['label_en']]) ?>
                        <span class="choice-text"><?= e(t('priority.help.' . $priority['code'])) ?></span>
                        <span class="choice-sla"><?= e(t('priority.sla', ['hours' => $priority['sla_resolution_h']])) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <?= $error('priority_id') ?>
    </fieldset>

    <div class="field <?= isset($errors['description']) ? 'has-error' : '' ?>">
        <label for="description"><?= e(t('ticket.description')) ?></label>
        <textarea id="description" name="description" rows="6" maxlength="5000" required
                  aria-describedby="description_hint" data-counter="description_count"><?= e($old['description'] ?? '') ?></textarea>
        <p class="hint hint-split">
            <span id="description_hint"><?= e(t('ticket.description_hint')) ?></span>
            <span class="counter" id="description_count"></span>
        </p>
        <?= $error('description') ?>
    </div>

    <div class="field <?= isset($errors['attachment']) ? 'has-error' : '' ?>">
        <label for="attachment"><?= e(t('ticket.attachment')) ?> <span class="optional"><?= e(t('form.optional')) ?></span></label>
        <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf"
               aria-describedby="attachment_hint" data-max-size="<?= $maxSize ?>"
               data-too-large="<?= e(t('attachment.too_large')) ?>">
        <p class="hint" id="attachment_hint"><?= e(t('ticket.attachment_hint', ['size' => (int) ($maxSize / 1048576)])) ?></p>
        <p class="error" data-file-error hidden></p>
        <?= $error('attachment') ?>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= e(t('ticket.submit')) ?></button>
        <a class="btn btn-quiet" href="<?= e(url('/tickets')) ?>"><?= e(t('form.cancel')) ?></a>
    </div>
</form>
