<?php
/**
 * Interface technicien : file d'attente globale des tickets.
 * Variables : $tickets (page courante), $pagination, $filters (valeurs en cours),
 *             $statuses, $priorities, $locations (lieux validés, proposés dans le filtre).
 */
use App\Core\Auth;
use App\Core\View;
use App\Models\Ticket;

$user = Auth::user();

/** Attribut « selected » d'une option de filtre. */
$selected = static fn (string $filter, string $value): string => ($filters[$filter] ?? '') === $value ? 'selected' : '';
?>
<div class="page-head">
    <h1><?= e(t('nav.queue')) ?></h1>
    <p class="head-count"><?= e(t($pagination->total > 1 ? 'queue.count_many' : 'queue.count_one', ['count' => $pagination->total])) ?></p>
</div>

<!-- Filtres : formulaire GET, pour qu'une vue filtrée puisse être mise en favori ou partagée -->
<form class="filters" method="get" action="<?= e(url('/queue')) ?>">
    <div class="field">
        <label for="f-scope"><?= e(t('queue.scope')) ?></label>
        <select id="f-scope" name="scope" data-autosubmit>
            <option value="all" <?= $selected('scope', 'all') ?>><?= e(t('queue.scope.all')) ?></option>
            <option value="mine" <?= $selected('scope', 'mine') ?>><?= e(t('queue.scope.mine')) ?></option>
            <option value="unassigned" <?= $selected('scope', 'unassigned') ?>><?= e(t('queue.scope.unassigned')) ?></option>
        </select>
    </div>
    <div class="field">
        <label for="f-status"><?= e(t('ticket.status')) ?></label>
        <select id="f-status" name="status" data-autosubmit>
            <option value="open" <?= $selected('status', 'open') ?>><?= e(t('queue.status.open')) ?></option>
            <option value="all" <?= $selected('status', 'all') ?>><?= e(t('queue.status.all')) ?></option>
            <?php foreach ($statuses as $code => $row): ?>
                <option value="<?= e($code) ?>" <?= $selected('status', $code) ?>><?= e(label($row, 'label')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-priority"><?= e(t('ticket.priority')) ?></label>
        <select id="f-priority" name="priority" data-autosubmit>
            <option value=""><?= e(t('queue.any')) ?></option>
            <?php foreach (array_reverse($priorities) as $row): ?>
                <option value="<?= e($row['code']) ?>" <?= $selected('priority', $row['code']) ?>><?= e(label($row, 'label')) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f-location"><?= e(t('ticket.location')) ?></label>
        <input type="search" id="f-location" name="location" value="<?= e($filters['location']) ?>"
               list="queue-location-list" maxlength="80" placeholder="<?= e(t('queue.location_placeholder')) ?>" data-autosubmit>
        <datalist id="queue-location-list">
            <?php foreach ($locations as $location): ?>
                <option value="<?= e($location['name']) ?>"></option>
            <?php endforeach; ?>
        </datalist>
    </div>
    <div class="field">
        <label for="f-level"><?= e(t('ticket.level')) ?></label>
        <select id="f-level" name="level" data-autosubmit>
            <option value=""><?= e(t('queue.any')) ?></option>
            <?php foreach (['1', '2', '3'] as $level): ?>
                <option value="<?= $level ?>" <?= $selected('level', $level) ?>><?= e(t('tech.level', ['level' => $level])) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filters-actions">
        <button class="btn" type="submit" data-hide-with-js><?= e(t('queue.apply')) ?></button>
        <a class="btn btn-quiet" href="<?= e(url('/queue')) ?>"><?= e(t('queue.reset')) ?></a>
    </div>
</form>

<?php if ($tickets === []): ?>
    <div class="empty">
        <p><?= e(t('queue.empty')) ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('ticket.priority')) ?></th>
                    <th scope="col"><?= e(t('ticket.subject')) ?></th>
                    <th scope="col"><?= e(t('ticket.location')) ?></th>
                    <th scope="col"><?= e(t('ticket.status')) ?></th>
                    <th scope="col"><?= e(t('ticket.technician')) ?></th>
                    <th scope="col"><?= e(t('ticket.due_at')) ?></th>
                    <th scope="col"><span class="sr-only"><?= e(t('tech.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr class="<?= (int) $ticket['is_overdue'] === 1 ? 'is-late' : '' ?>">
                        <td data-label="<?= e(t('ticket.priority')) ?>"><?= priority_badge($ticket) ?></td>
                        <td data-label="<?= e(t('ticket.subject')) ?>">
                            <a class="row-title" href="<?= e(url('/tickets/' . $ticket['id'])) ?>"><?= e($ticket['title']) ?></a>
                            <span class="row-meta">
                                <span class="ref"><?= e($ticket['reference']) ?></span>
                                <?= e(label($ticket, 'category')) ?>, <?= e(person($ticket, 'user_')) ?><?= $ticket['user_title_fr'] !== null ? ' (' . e(label($ticket, 'user_title')) . ')' : '' ?>
                            </span>
                        </td>
                        <td data-label="<?= e(t('ticket.location')) ?>"><?= e($ticket['location_name']) ?></td>
                        <td data-label="<?= e(t('ticket.status')) ?>">
                            <?= status_badge($ticket) ?>
                            <span class="row-meta"><?= e(t('tech.level', ['level' => (int) $ticket['support_level']])) ?></span>
                        </td>
                        <td data-label="<?= e(t('ticket.technician')) ?>">
                            <?= $ticket['technician_id'] !== null ? e(person($ticket, 'tech_')) : '<span class="muted">' . e(t('ticket.unassigned')) . '</span>' ?>
                        </td>
                        <td data-label="<?= e(t('ticket.due_at')) ?>">
                            <?php if (Ticket::isOpen($ticket)): ?>
                                <?= e(dt($ticket['due_at'])) ?>
                                <?php if ((int) $ticket['is_overdue'] === 1): ?><span class="badge badge-late"><?= e(t('ticket.overdue')) ?></span><?php endif; ?>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="cell-action">
                            <?php if ($ticket['technician_id'] === null && Ticket::canTake($ticket, $user)): ?>
                                <form method="post" action="<?= e(url('/tickets/' . $ticket['id'] . '/take')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-small" type="submit"><?= e(t('tech.take')) ?></button>
                                </form>
                            <?php else: ?>
                                <a class="btn btn-small btn-quiet" href="<?= e(url('/tickets/' . $ticket['id'])) ?>"><?= e(t('queue.open')) ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?= View::render('partials/pagination', ['pagination' => $pagination, 'path' => '/queue', 'query' => $filters]) ?>
<?php endif; ?>
