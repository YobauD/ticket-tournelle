<?php
/**
 * Administration : journal d'audit, en lecture seule.
 * Variables : $rows, $targets (cible => libellé), $actions, $filters, $pagination.
 */
use App\Core\View;
use App\Models\Log;

/** Libellé traduit d'une action ; le code brut si aucune traduction n'existe. */
$actionLabel = static function (string $code): string {
    $key   = 'audit.action.' . $code;
    $label = t($key);
    return $label === $key ? $code : $label;
};
?>
<?= View::render('admin/tabs', ['current' => '/admin/logs']) ?>

<div class="page-head">
    <div>
        <h1><?= e(t('nav.logs')) ?></h1>
        <p class="head-meta"><?= e(t('audit.intro')) ?></p>
    </div>
</div>

<form class="filters" method="get" action="<?= e(url('/admin/logs')) ?>">
    <div class="field">
        <label for="f-action"><?= e(t('audit.action')) ?></label>
        <select id="f-action" name="action" data-autosubmit>
            <option value=""><?= e(t('queue.any')) ?></option>
            <?php foreach ($actions as $code): ?>
                <option value="<?= e($code) ?>" <?= $filters['action'] === $code ? 'selected' : '' ?>><?= e($actionLabel($code)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-q"><?= e(t('audit.search')) ?></label>
        <input type="search" id="f-q" name="q" value="<?= e($filters['q']) ?>" maxlength="100"
               placeholder="<?= e(t('audit.search_hint')) ?>">
    </div>
    <div class="field">
        <label for="f-from"><?= e(t('audit.from')) ?></label>
        <input type="date" id="f-from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="field">
        <label for="f-to"><?= e(t('audit.to')) ?></label>
        <input type="date" id="f-to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit"><?= e(t('queue.apply')) ?></button>
        <a class="btn btn-quiet" href="<?= e(url('/admin/logs')) ?>"><?= e(t('queue.reset')) ?></a>
    </div>
</form>

<?php if ($rows === []): ?>
    <div class="empty"><p><?= e(t('audit.empty')) ?></p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table-stack table-compact">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('audit.date')) ?></th>
                    <th scope="col"><?= e(t('audit.author')) ?></th>
                    <th scope="col"><?= e(t('audit.action')) ?></th>
                    <th scope="col"><?= e(t('audit.target')) ?></th>
                    <th scope="col"><?= e(t('audit.details')) ?></th>
                    <th scope="col"><?= e(t('audit.ip')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $isSecurity = in_array($row['action'], Log::SECURITY_ACTIONS, true); ?>
                    <tr class="<?= $isSecurity ? 'is-alert' : '' ?>">
                        <td data-label="<?= e(t('audit.date')) ?>" class="nowrap"><?= e(dt($row['created_at'])) ?></td>
                        <td data-label="<?= e(t('audit.author')) ?>">
                            <?php if ($row['user_id'] !== null): ?>
                                <?= e(person($row)) ?><span class="row-meta"><?= e($row['email']) ?></span>
                            <?php else: ?>
                                <span class="muted"><?= e(t('audit.anonymous')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= e(t('audit.action')) ?>">
                            <span class="<?= $isSecurity ? 'badge badge-late' : 'tag' ?>"><?= e($actionLabel($row['action'])) ?></span>
                        </td>
                        <td data-label="<?= e(t('audit.target')) ?>">
                            <?php if (isset($targets[$row['target']])): ?>
                                <?= e($targets[$row['target']]) ?><span class="row-meta"><?= e($row['target']) ?></span>
                            <?php else: ?>
                                <?= e($row['target'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= e(t('audit.details')) ?>" class="cell-wrap"><?= e($row['details'] ?? '') ?></td>
                        <td data-label="<?= e(t('audit.ip')) ?>" class="nowrap"><?= e($row['ip_address'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= View::render('partials/pagination', ['pagination' => $pagination, 'path' => '/admin/logs', 'query' => $filters]) ?>
<?php endif; ?>
