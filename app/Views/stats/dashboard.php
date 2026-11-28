<?php
/**
 * Tableau de bord statistique.
 * Variables : voir StatsController::index().
 *
 * Les graphiques sont des SVG dont les dimensions sont des attributs : la politique
 * de sécurité (CSP) interdit les styles écrits dans le HTML, et aucune bibliothèque
 * externe n'est chargée. Chaque barre est accompagnée de sa valeur en texte.
 */
use App\Models\Stats;

$maxCategory = max([1, ...array_map(static fn (array $c): int => (int) $c['ticket_count'], $categories)]);
$maxTimeline = max([1, ...array_column($timeline, 'count')]);
?>
<div class="page-head">
    <div>
        <h1><?= e(t('nav.stats')) ?></h1>
        <p class="head-meta"><?= e(t('stats.range', ['from' => dt($start->format('Y-m-d'), false), 'to' => dt(date('Y-m-d'), false)])) ?></p>
    </div>
    <form class="period" method="get" action="<?= e(url('/stats')) ?>">
        <label for="period"><?= e(t('stats.period')) ?></label>
        <select id="period" name="period" data-autosubmit>
            <?php foreach ($periods as $code): ?>
                <option value="<?= e($code) ?>" <?= $code === $period ? 'selected' : '' ?>><?= e(t('stats.period.' . $code)) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn-small" type="submit">OK</button></noscript>
    </form>
</div>

<!-- Indicateurs clés -->
<div class="kpis">
    <section class="kpi">
        <h2 class="kpi-label"><?= e(t('stats.declared')) ?></h2>
        <p class="kpi-value"><?= (int) $declared ?></p>
        <p class="kpi-sub"><?= e(t('stats.declared_sub')) ?></p>
    </section>
    <section class="kpi">
        <h2 class="kpi-label"><?= e(t('stats.open_now')) ?></h2>
        <p class="kpi-value"><?= (int) $openNow ?></p>
        <p class="kpi-sub"><?= e(t('stats.open_now_sub')) ?></p>
    </section>
    <section class="kpi">
        <h2 class="kpi-label"><?= e(t('stats.take_average')) ?></h2>
        <p class="kpi-value"><?= e(duration($takeAverage)) ?></p>
        <p class="kpi-sub"><?= e(t('stats.take_average_sub')) ?></p>
    </section>
    <section class="kpi kpi-main">
        <h2 class="kpi-label"><?= e(t('stats.resolution_average')) ?></h2>
        <p class="kpi-value"><?= e(duration($average)) ?></p>
        <p class="kpi-sub"><?= e(t('stats.resolution_sub', ['median' => duration($median), 'count' => $resolved])) ?></p>
    </section>
    <section class="kpi">
        <h2 class="kpi-label"><?= e(t('stats.sla')) ?></h2>
        <p class="kpi-value"><?= e(percent($withinSla, $resolved)) ?></p>
        <p class="kpi-sub"><?= e(t('stats.sla_sub', ['ok' => $withinSla, 'count' => $resolved])) ?></p>
    </section>
    <section class="kpi kpi-main">
        <h2 class="kpi-label"><?= e(t('stats.recurrence')) ?></h2>
        <p class="kpi-value"><?= e(percent($recurrence['recurrent'], $recurrence['total'])) ?></p>
        <p class="kpi-sub"><?= e(t('stats.recurrence_sub', ['count' => $recurrence['recurrent'], 'total' => $recurrence['total']])) ?></p>
    </section>
</div>

<div class="stats-grid">
    <!-- Volume par catégorie -->
    <section class="card">
        <h2><?= e(t('stats.by_category')) ?></h2>
        <?php if ($declared === 0): ?>
            <p class="muted"><?= e(t('stats.no_ticket')) ?></p>
        <?php else: ?>
            <table class="bars">
                <thead class="sr-only">
                    <tr><th scope="col"><?= e(t('ticket.category')) ?></th><td></td><th scope="col"><?= e(t('stats.tickets')) ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <?php $n = (int) $category['ticket_count']; ?>
                        <tr>
                            <th scope="row"><?= e(label($category, 'label')) ?></th>
                            <td class="bars-track">
                                <svg class="hbar" viewBox="0 0 100 1" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                    <rect class="hbar-track" width="100" height="1"/>
                                    <rect class="hbar-fill" width="<?= svg_number($n * 100 / $maxCategory) ?>" height="1"/>
                                </svg>
                            </td>
                            <td class="bars-value"><?= $n ?> <span class="muted">(<?= e(percent($n, $declared)) ?>)</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Évolution -->
    <section class="card">
        <h2><?= e(t('stats.timeline')) ?></h2>
        <div class="columns-wrap" tabindex="0" role="region" aria-label="<?= e(t('stats.timeline')) ?>">
            <ol class="columns" aria-label="<?= e(t('stats.timeline')) ?>">
                <?php foreach ($timeline as $bucket): ?>
                    <?php $h = $bucket['count'] * 100 / $maxTimeline; ?>
                    <li>
                        <span class="columns-value"><?= (int) $bucket['count'] ?></span>
                        <svg class="vbar" viewBox="0 0 1 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                            <rect class="hbar-fill" y="<?= svg_number(100 - $h) ?>" width="1" height="<?= svg_number($h) ?>"/>
                        </svg>
                        <span class="columns-label"><?= e($bucket['label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <!-- Délais par priorité -->
    <section class="card">
        <h2><?= e(t('stats.by_priority')) ?></h2>
        <div class="table-wrap table-inner">
            <table class="table table-compact">
                <thead>
                    <tr>
                        <th scope="col"><?= e(t('ticket.priority')) ?></th>
                        <th scope="col"><?= e(t('stats.resolved')) ?></th>
                        <th scope="col"><?= e(t('stats.average')) ?></th>
                        <th scope="col"><?= e(t('stats.target')) ?></th>
                        <th scope="col"><?= e(t('stats.on_time')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byPriority as $row): ?>
                        <?php $p = $row['priority']; ?>
                        <tr>
                            <td><?= priority_badge(['priority_code' => $p['code'], 'priority_fr' => $p['label_fr'], 'priority_en' => $p['label_en']]) ?></td>
                            <td><?= (int) $row['count'] ?></td>
                            <td class="nowrap"><?= e(duration($row['average'])) ?></td>
                            <td class="nowrap"><?= (int) $p['sla_resolution_h'] ?> <?= e(t('unit.h')) ?></td>
                            <td class="nowrap <?= $row['count'] > 0 && $row['within'] < $row['count'] ? 'is-warning' : '' ?>">
                                <?= e(percent($row['within'], $row['count'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Incidents récurrents -->
    <section class="card">
        <h2><?= e(t('stats.hotspots')) ?></h2>
        <?php if ($hotspots === []): ?>
            <p class="muted"><?= e(t('stats.no_hotspot')) ?></p>
        <?php else: ?>
            <div class="table-wrap table-inner">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th scope="col"><?= e(t('ticket.location')) ?></th>
                            <th scope="col"><?= e(t('ticket.category')) ?></th>
                            <th scope="col"><?= e(t('stats.tickets')) ?></th>
                            <th scope="col"><?= e(t('stats.last')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotspots as $spot): ?>
                            <tr>
                                <td><?= e($spot['location_name']) ?></td>
                                <td><?= e(label($spot, 'label')) ?></td>
                                <td><strong><?= (int) $spot['ticket_count'] ?></strong></td>
                                <td class="nowrap"><?= e(dt($spot['last_at'], false)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<p class="footnote"><?= e(t('stats.footnote', ['days' => Stats::RECURRENCE_DAYS])) ?></p>
