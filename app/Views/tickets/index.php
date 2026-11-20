<?php
/**
 * Tableau de bord du déclarant : ses tickets et leur avancement.
 * Variables : $tickets, $counts (code de statut => nombre), $scope (all | active | done).
 */
$total  = array_sum($counts);
$done   = ($counts['closed'] ?? 0) + ($counts['rejected'] ?? 0);
$tabs   = ['all' => $total, 'active' => $total - $done, 'done' => $done];
$toDo   = ($counts['pending'] ?? 0) + ($counts['resolved'] ?? 0);
?>
<div class="page-head">
    <h1><?= e(t('nav.my_tickets')) ?></h1>
    <!-- Sur grand écran, ce bouton est déjà dans la barre supérieure -->
    <a class="btn btn-primary only-mobile" href="<?= e(url('/tickets/new')) ?>"><?= e(t('ticket.new')) ?></a>
</div>

<?php if ($toDo > 0): ?>
    <div class="alert alert-action" role="status">
        <?= e(t($toDo > 1 ? 'dashboard.todo_many' : 'dashboard.todo_one', ['count' => $toDo])) ?>
    </div>
<?php endif; ?>

<nav class="tabs" aria-label="<?= e(t('dashboard.filter')) ?>">
    <?php foreach ($tabs as $key => $count): ?>
        <a href="<?= e(url('/tickets?scope=' . $key)) ?>" <?= $scope === $key ? 'aria-current="true"' : '' ?>>
            <?= e(t('dashboard.tab.' . $key)) ?> <span class="count"><?= (int) $count ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($tickets === []): ?>
    <div class="empty">
        <p><?= e(t($total === 0 ? 'dashboard.empty' : 'dashboard.empty_filter')) ?></p>
        <?php if ($total === 0): ?>
            <a class="btn btn-primary" href="<?= e(url('/tickets/new')) ?>"><?= e(t('ticket.new')) ?></a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table table-stack">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('ticket.reference')) ?></th>
                    <th scope="col"><?= e(t('ticket.subject')) ?></th>
                    <th scope="col"><?= e(t('ticket.priority')) ?></th>
                    <th scope="col"><?= e(t('ticket.status')) ?></th>
                    <th scope="col"><?= e(t('ticket.technician')) ?></th>
                    <th scope="col"><?= e(t('ticket.created_at')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td data-label="<?= e(t('ticket.reference')) ?>">
                            <a class="ref" href="<?= e(url('/tickets/' . $ticket['id'])) ?>"><?= e($ticket['reference']) ?></a>
                        </td>
                        <td data-label="<?= e(t('ticket.subject')) ?>">
                            <a class="row-title" href="<?= e(url('/tickets/' . $ticket['id'])) ?>"><?= e($ticket['title']) ?></a>
                            <span class="row-meta"><?= e(label($ticket, 'category')) ?>, <?= e($ticket['location_name']) ?></span>
                        </td>
                        <td data-label="<?= e(t('ticket.priority')) ?>"><?= priority_badge($ticket) ?></td>
                        <td data-label="<?= e(t('ticket.status')) ?>">
                            <?= status_badge($ticket) ?>
                            <?php if (in_array($ticket['status_code'], ['pending', 'resolved'], true)): ?>
                                <span class="row-meta row-todo"><?= e(t('dashboard.todo.' . $ticket['status_code'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= e(t('ticket.technician')) ?>">
                            <?= $ticket['technician_id'] !== null ? e(person($ticket, 'tech_')) : '<span class="muted">' . e(t('ticket.unassigned')) . '</span>' ?>
                        </td>
                        <td data-label="<?= e(t('ticket.created_at')) ?>"><?= e(dt($ticket['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
