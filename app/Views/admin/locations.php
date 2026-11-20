<?php
/**
 * Administration : lieux (salles et bureaux).
 * Variables : $locations (avec ticket_count), $unverified (nombre de lieux à vérifier), $old, $errors.
 *
 * Les lieux saisis par les déclarants arrivent « à vérifier », en tête de liste.
 * Enregistrer une ligne la valide ; « Fusionner » la remplace par un autre lieu.
 */
use App\Core\View;

$error = static fn (string $field): string => isset($errors[$field])
    ? '<p class="error">' . e($errors[$field]) . '</p>'
    : '';

// Lieux possibles comme cible d'une fusion : les lieux validés d'abord
$targets = $locations;
usort($targets, static fn (array $a, array $b): int => [-(int) $a['is_verified'], $a['name']] <=> [-(int) $b['is_verified'], $b['name']]);
?>
<?= View::render('admin/tabs', ['current' => '/admin/locations']) ?>

<div class="page-head">
    <div>
        <h1><?= e(t('nav.locations')) ?></h1>
        <p class="head-meta"><?= e(t('ref.locations_intro')) ?></p>
    </div>
    <a class="btn btn-primary" href="#new-location"><?= e(t('ref.new_location')) ?></a>
</div>

<?php if ($unverified > 0): ?>
    <div class="alert alert-action" role="status">
        <?= e(t($unverified > 1 ? 'ref.unverified_many' : 'ref.unverified_one', ['count' => $unverified])) ?>
    </div>
<?php endif; ?>

<div class="table-wrap">
    <table class="table table-stack">
        <thead>
            <tr>
                <th scope="col"><?= e(t('ref.location_name')) ?></th>
                <th scope="col"><?= e(t('ref.tickets')) ?></th>
                <th scope="col"><?= e(t('ref.active')) ?></th>
                <th scope="col"><span class="sr-only"><?= e(t('tech.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $location): ?>
                <?php
                $lid      = (int) $location['id'];
                $formId   = 'location-' . $lid;
                $verified = (int) $location['is_verified'] === 1;
                ?>
                <tr class="<?= !$verified ? 'is-unverified' : ((int) $location['is_active'] === 1 ? '' : 'is-inactive') ?>">
                    <td data-label="<?= e(t('ref.location_name')) ?>">
                        <label class="sr-only" for="n-<?= $lid ?>"><?= e(t('ref.location_name')) ?></label>
                        <input type="text" id="n-<?= $lid ?>" name="name" form="<?= $formId ?>"
                               value="<?= e($location['name']) ?>" maxlength="80" required>
                        <?php if (!$verified): ?>
                            <span class="row-meta">
                                <span class="badge badge-status-pending"><?= e(t('ref.unverified')) ?></span>
                                <?= e(t('ref.proposed_on', ['date' => dt($location['created_at'])])) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= e(t('ref.tickets')) ?>"><?= (int) $location['ticket_count'] ?></td>
                    <td data-label="<?= e(t('ref.active')) ?>">
                        <label class="check">
                            <input type="checkbox" name="is_active" value="1" form="<?= $formId ?>"
                                   <?= (int) $location['is_active'] === 1 ? 'checked' : '' ?>>
                            <span class="sr-only"><?= e(t('ref.active')) ?></span>
                        </label>
                        <?php if ((int) $location['is_active'] !== 1): ?><span class="tag"><?= e(t('ref.disabled')) ?></span><?php endif; ?>
                    </td>
                    <td class="cell-action">
                        <form id="<?= $formId ?>" method="post" action="<?= e(url('/admin/locations/' . $lid)) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-small <?= $verified ? '' : 'btn-primary' ?>" type="submit">
                                <?= e(t($verified ? 'form.save' : 'ref.validate')) ?>
                            </button>
                        </form>
                        <?php if (count($locations) > 1): ?>
                            <details class="popover">
                                <summary class="btn btn-small btn-quiet"><?= e(t('ref.merge')) ?></summary>
                                <form method="post" action="<?= e(url('/admin/locations/' . $lid . '/merge')) ?>">
                                    <?= csrf_field() ?>
                                    <label for="m-<?= $lid ?>"><?= e(t('ref.merge_into', ['name' => $location['name']])) ?></label>
                                    <select id="m-<?= $lid ?>" name="target_id" required>
                                        <?php foreach ($targets as $target): ?>
                                            <?php if ((int) $target['id'] !== $lid): ?>
                                                <option value="<?= (int) $target['id'] ?>">
                                                    <?= e($target['name']) ?><?= (int) $target['is_verified'] === 1 ? '' : ' (' . e(t('ref.unverified')) . ')' ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="hint"><?= e(t('ref.merge_hint', ['name' => $location['name'], 'count' => (int) $location['ticket_count']])) ?></p>
                                    <button class="btn btn-small" type="submit"><?= e(t('ref.merge_submit')) ?></button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<section class="card form narrow" id="new-location">
    <h2><?= e(t('ref.new_location')) ?></h2>

    <form method="post" action="<?= e(url('/admin/locations')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <label for="new_name"><?= e(t('ref.location_name')) ?></label>
            <input type="text" id="new_name" name="name" value="<?= e($old['name'] ?? '') ?>"
                   maxlength="80" required aria-describedby="new_name_hint">
            <p class="hint" id="new_name_hint"><?= e(t('ref.location_hint')) ?></p>
            <?= $error('name') ?>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= e(t('ref.add_location')) ?></button>
        </div>
    </form>
</section>
