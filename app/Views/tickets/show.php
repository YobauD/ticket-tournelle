<?php
/**
 * Détail d'un ticket. Écran partagé : le déclarant y suit sa demande,
 * le technicien y traite le ticket (les blocs d'action dépendent des droits).
 *
 * Variables : $ticket, $comments, $history, $attachments, $statuses,
 *             $isOwner, $isStaff, $canTake, $canChangeStatus, $targets.
 */
use App\Core\Config;
use App\Models\Ticket;

$id      = (int) $ticket['id'];
$status  = $ticket['status_code'];
$isFinal = (int) $ticket['is_final'] === 1;
$isOpen  = Ticket::isOpen($ticket);

// Fil chronologique unique : messages et événements de l'historique mélangés
$timeline = [];
foreach ($comments as $comment) {
    $timeline[] = ['type' => 'comment', 'at' => $comment['created_at'], 'row' => $comment];
}
foreach ($history as $event) {
    $timeline[] = ['type' => 'event', 'at' => $event['created_at'], 'row' => $event];
}
usort($timeline, static fn (array $a, array $b): int => [$a['at'], $a['type'] === 'comment'] <=> [$b['at'], $b['type'] === 'comment']);

// Étapes de la frise. « En attente » est un état de l'étape « En cours », pas une étape de plus.
$steps   = ['new', 'in_progress', 'resolved', 'closed'];
$current = array_search($status === 'pending' ? 'in_progress' : $status, $steps, true);
?>
<div class="page-head">
    <div>
        <a class="back" href="<?= e(url($isStaff ? '/queue' : '/tickets')) ?>"><?= e(t($isStaff ? 'ticket.back_queue' : 'ticket.back_mine')) ?></a>
        <h1><?= e($ticket['title']) ?></h1>
        <p class="head-meta">
            <span class="ref"><?= e($ticket['reference']) ?></span>
            <?= e(t('ticket.declared_by', ['date' => dt($ticket['created_at']), 'name' => person($ticket, 'user_')])) ?>
        </p>
    </div>
    <?= status_badge($ticket) ?>
</div>

<?php if ($status === 'rejected'): ?>
    <div class="alert alert-error" role="status"><?= e(t('ticket.rejected_notice')) ?></div>
<?php else: ?>
    <!-- Frise du cycle de vie -->
    <ol class="track" aria-label="<?= e(t('ticket.progress')) ?>">
        <?php foreach ($steps as $index => $code): ?>
            <li class="<?= $index < $current ? 'is-done' : ($index === $current ? 'is-current' : '') ?>"
                <?= $index === $current ? 'aria-current="step"' : '' ?>>
                <span class="track-dot" aria-hidden="true"></span>
                <span class="track-label"><?= e(label($statuses[$code], 'label')) ?></span>
                <?php if ($code === 'in_progress' && $status === 'pending'): ?>
                    <span class="track-note"><?= e(t('ticket.pending_note')) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<div class="ticket-layout">
    <div class="ticket-main">

        <section class="card">
            <h2><?= e(t('ticket.description')) ?></h2>
            <p class="prose"><?= nl2br(e($ticket['description'])) ?></p>

            <?php if ($attachments !== []): ?>
                <h3><?= e(t('ticket.attachments')) ?></h3>
                <ul class="files">
                    <?php foreach ($attachments as $file): ?>
                        <li>
                            <a href="<?= e(url('/attachments/' . $file['id'])) ?>"><?= e($file['original_name']) ?></a>
                            <span class="muted">(<?= e(number_format($file['size_bytes'] / 1024, 0, ',', ' ')) ?> <?= e(t('unit.kb')) ?>)</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><?= e(t('ticket.activity')) ?></h2>
            <ol class="timeline">
                <?php foreach ($timeline as $item): ?>
                    <?php $row = $item['row']; ?>
                    <?php if ($item['type'] === 'comment'): ?>
                        <li class="message <?= (int) $row['is_internal'] === 1 ? 'is-internal' : '' ?>">
                            <p class="message-head">
                                <strong><?= e(person($row)) ?></strong>
                                <?php if ($row['role_code'] !== 'user'): ?><span class="tag"><?= e(t('role.support')) ?></span><?php endif; ?>
                                <?php if ((int) $row['is_internal'] === 1): ?><span class="tag tag-internal"><?= e(t('comment.internal')) ?></span><?php endif; ?>
                                <time datetime="<?= e($row['created_at']) ?>"><?= e(dt($row['created_at'])) ?></time>
                            </p>
                            <p class="prose"><?= nl2br(e($row['body'])) ?></p>
                        </li>
                    <?php else: ?>
                        <li class="event">
                            <span>
                                <?= e($row['user_id'] !== null ? person($row) : t('history.system')) ?>
                                <?= e(t('history.' . $row['event'], [
                                    'old'  => label($row, 'old'),
                                    'new'  => label($row, 'new'),
                                    'note' => (string) $row['note'],
                                    'days' => Config::get('tickets.auto_close_days'),
                                ])) ?>
                            </span>
                            <time datetime="<?= e($row['created_at']) ?>"><?= e(dt($row['created_at'])) ?></time>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>

            <?php if (!$isFinal): ?>
                <form class="reply" method="post" action="<?= e(url("/tickets/$id/comments")) ?>">
                    <?= csrf_field() ?>
                    <label for="body"><?= e(t('comment.add')) ?></label>
                    <textarea id="body" name="body" rows="3" maxlength="5000" required></textarea>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><?= e(t('comment.send')) ?></button>
                        <?php if ($isStaff): ?>
                            <label class="check">
                                <input type="checkbox" name="is_internal" value="1">
                                <?= e(t('comment.internal_option')) ?>
                            </label>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <aside class="ticket-side">

        <?php if ($isOwner && $status === 'resolved'): ?>
            <!-- Le déclarant valide ou conteste la résolution -->
            <section class="card card-action">
                <h2><?= e(t('owner.confirm_title')) ?></h2>
                <p><?= e(t('owner.confirm_text', ['days' => Config::get('tickets.auto_close_days')])) ?></p>
                <form method="post" action="<?= e(url("/tickets/$id/close")) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary btn-block" type="submit"><?= e(t('owner.confirm')) ?></button>
                </form>
                <details>
                    <summary><?= e(t('owner.reopen')) ?></summary>
                    <form method="post" action="<?= e(url("/tickets/$id/reopen")) ?>">
                        <?= csrf_field() ?>
                        <label for="reopen_body"><?= e(t('owner.reopen_reason')) ?></label>
                        <textarea id="reopen_body" name="body" rows="3" maxlength="5000" required></textarea>
                        <button class="btn btn-block" type="submit"><?= e(t('owner.reopen_submit')) ?></button>
                    </form>
                </details>
            </section>
        <?php endif; ?>

        <?php if ($isStaff && ($canTake || ($canChangeStatus && $targets !== []))): ?>
            <!-- Actions du technicien -->
            <section class="card card-action">
                <h2><?= e(t('tech.actions')) ?></h2>

                <?php if ($canTake): ?>
                    <form method="post" action="<?= e(url("/tickets/$id/take")) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary btn-block" type="submit">
                            <?= e(t($ticket['technician_id'] === null ? 'tech.take' : 'tech.take_over')) ?>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canChangeStatus && $targets !== []): ?>
                    <form method="post" action="<?= e(url("/tickets/$id/status")) ?>">
                        <?= csrf_field() ?>
                        <label for="status"><?= e(t('tech.change_status')) ?></label>
                        <select id="status" name="status" required data-status-select
                                data-note-required="<?= e(implode(',', Ticket::NOTE_REQUIRED)) ?>">
                            <?php foreach ($targets as $code): ?>
                                <option value="<?= e($code) ?>"><?= e(label($statuses[$code], 'label')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="note"><?= e(t('tech.note')) ?></label>
                        <textarea id="note" name="note" rows="3" maxlength="5000" data-status-note
                                  aria-describedby="note_hint"></textarea>
                        <p class="hint" id="note_hint"><?= e(t('tech.note_hint')) ?></p>
                        <button class="btn btn-block" type="submit"><?= e(t('tech.apply_status')) ?></button>
                    </form>
                <?php endif; ?>

                <?php if ($canChangeStatus && $isOpen && (int) $ticket['support_level'] < 3): ?>
                    <details>
                        <summary><?= e(t('tech.escalate')) ?></summary>
                        <form method="post" action="<?= e(url("/tickets/$id/escalate")) ?>">
                            <?= csrf_field() ?>
                            <label for="level"><?= e(t('tech.escalate_to')) ?></label>
                            <select id="level" name="level" required>
                                <?php for ($level = (int) $ticket['support_level'] + 1; $level <= 3; $level++): ?>
                                    <option value="<?= $level ?>"><?= e(t('tech.level', ['level' => $level])) ?></option>
                                <?php endfor; ?>
                            </select>
                            <label for="escalate_note"><?= e(t('tech.escalate_reason')) ?></label>
                            <textarea id="escalate_note" name="note" rows="3" maxlength="5000" required></textarea>
                            <button class="btn btn-block" type="submit"><?= e(t('tech.escalate_submit')) ?></button>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="card">
            <h2><?= e(t('ticket.details')) ?></h2>
            <dl class="details">
                <dt><?= e(t('ticket.priority')) ?></dt>
                <dd><?= priority_badge($ticket) ?></dd>

                <dt><?= e(t('ticket.category')) ?></dt>
                <dd><?= e(label($ticket, 'category')) ?></dd>

                <dt><?= e(t('ticket.location')) ?></dt>
                <dd><?= e($ticket['location_name']) ?></dd>

                <dt><?= e(t('ticket.declarant')) ?></dt>
                <dd>
                    <?= e(person($ticket, 'user_')) ?>
                    <?php if ($ticket['user_title_fr'] !== null): ?><span class="row-meta"><?= e(label($ticket, 'user_title')) ?></span><?php endif; ?>
                </dd>

                <dt><?= e(t('ticket.technician')) ?></dt>
                <dd>
                    <?= $ticket['technician_id'] !== null ? e(person($ticket, 'tech_')) : '<span class="muted">' . e(t('ticket.unassigned')) . '</span>' ?>
                </dd>

                <dt><?= e(t('ticket.level')) ?></dt>
                <dd><?= e(t('tech.level', ['level' => (int) $ticket['support_level']])) ?></dd>

                <?php if ($isOpen): ?>
                    <dt><?= e(t('ticket.due_at')) ?></dt>
                    <dd>
                        <?= e(dt($ticket['due_at'])) ?>
                        <?php if ((int) $ticket['is_overdue'] === 1): ?><span class="badge badge-late"><?= e(t('ticket.overdue')) ?></span><?php endif; ?>
                    </dd>
                <?php endif; ?>

                <?php if ($ticket['resolved_at'] !== null): ?>
                    <dt><?= e(t('ticket.resolved_at')) ?></dt>
                    <dd><?= e(dt($ticket['resolved_at'])) ?></dd>
                <?php endif; ?>

                <?php if ($ticket['closed_at'] !== null): ?>
                    <dt><?= e(t('ticket.closed_at')) ?></dt>
                    <dd><?= e(dt($ticket['closed_at'])) ?></dd>
                <?php endif; ?>
            </dl>
        </section>
    </aside>
</div>
