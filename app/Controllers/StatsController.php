<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Priority;
use App\Models\Stats;
use DateTimeImmutable;

/**
 * Tableau de bord statistique (US08) : volume, délais, récurrence.
 * Réservé à l'administrateur ; pour l'ouvrir à un futur rôle « Direction »,
 * il suffira d'ajouter ce rôle à la route /stats dans config/routes.php.
 */
final class StatsController extends Controller
{
    /** Périodes proposées. 'school' : depuis le 1er septembre de l'année scolaire en cours. */
    private const PERIODS = ['30d', '90d', 'school', 'all'];

    public function index(): void
    {
        $period = $this->query('period', 'school');
        if (!in_array($period, self::PERIODS, true)) {
            $period = 'school';
        }
        $start = $this->periodStart($period);
        $from  = $start->format('Y-m-d H:i:s');

        // --- Résolutions : moyenne, médiane, respect des délais -----------------
        $resolutions = Stats::resolutions($from);
        $minutes     = array_map(static fn (array $r): int => (int) $r['minutes'], $resolutions);
        $withinSla   = count(array_filter(
            $resolutions,
            static fn (array $r): bool => (int) $r['minutes'] <= (int) $r['sla_resolution_h'] * 60
        ));

        // --- Détail par priorité, de la plus urgente à la moins urgente ----------
        $byPriority = [];
        foreach (array_reverse(Priority::all()) as $priority) {
            $rows = array_values(array_filter(
                $resolutions,
                static fn (array $r): bool => $r['priority_code'] === $priority['code']
            ));
            $list = array_map(static fn (array $r): int => (int) $r['minutes'], $rows);
            $ok   = count(array_filter($list, static fn (int $m): bool => $m <= (int) $priority['sla_resolution_h'] * 60));

            $byPriority[] = [
                'priority' => $priority,
                'count'    => count($list),
                'average'  => $this->average($list),
                'within'   => $ok,
            ];
        }

        $this->render('stats/dashboard', [
            'title'       => t('nav.stats'),
            'period'      => $period,
            'periods'     => self::PERIODS,
            'start'       => $start,
            'declared'    => Stats::declared($from),
            'openNow'     => Stats::openNow(),
            'takeAverage' => Stats::averageTakeMinutes($from),
            'resolved'    => count($minutes),
            'average'     => $this->average($minutes),
            'median'      => $this->median($minutes),
            'withinSla'   => $withinSla,
            'byPriority'  => $byPriority,
            'recurrence'  => Stats::recurrence($from),
            'hotspots'    => Stats::hotspots($from),
            'categories'  => Stats::byCategory($from),
            'timeline'    => $this->timeline($start, Stats::perDay($from)),
        ]);
    }

    /** Début de la période, à minuit. */
    private function periodStart(string $period): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today');

        return match ($period) {
            '30d'    => $today->modify('-30 days'),
            '90d'    => $today->modify('-90 days'),
            // L'année scolaire commence le 1er septembre : en novembre 2026, c'est le 01/09/2026 ;
            // en mars 2027, c'est encore le 01/09/2026
            'school' => new DateTimeImmutable(((int) $today->format('n') >= 9 ? $today->format('Y') : (int) $today->format('Y') - 1) . '-09-01'),
            default  => new DateTimeImmutable(
                ($first = Stats::firstTicketDate()) !== null ? substr($first, 0, 10) : $today->format('Y-m-d')
            ),
        };
    }

    /**
     * Tickets déclarés regroupés par semaine (période de 3 mois au plus) ou par mois.
     * Les semaines ou mois sans ticket apparaissent avec 0, pour que le graphique ne mente pas.
     *
     * @param array<string, int> $perDay '2026-11-02' => 3
     * @return list<array{label: string, count: int}>
     */
    private function timeline(DateTimeImmutable $start, array $perDay): array
    {
        $today  = new DateTimeImmutable('today');
        $weekly = $start->diff($today)->days <= 92;

        // Premier jour de chaque groupe : le lundi de la semaine, ou le 1er du mois
        $cursor = $weekly
            ? $start->modify('monday this week')
            : $start->modify('first day of this month');
        $step = $weekly ? '+1 week' : '+1 month';
        $multiYear = $start->format('Y') !== $today->format('Y');

        $buckets = [];
        while ($cursor <= $today) {
            $next  = $cursor->modify($step);
            $count = 0;
            foreach ($perDay as $day => $n) {
                if ($day >= $cursor->format('Y-m-d') && $day < $next->format('Y-m-d')) {
                    $count += (int) $n;
                }
            }

            $buckets[] = [
                'label' => $weekly
                    ? t('stats.week_of', ['date' => $cursor->format(t('stats.week_format'))])
                    : t('stats.month.' . $cursor->format('n')) . ($multiYear ? ' ' . $cursor->format('y') : ''),
                'count' => $count,
            ];
            $cursor = $next;
        }
        return $buckets;
    }

    private function average(array $values): ?float
    {
        return $values === [] ? null : array_sum($values) / count($values);
    }

    /** Médiane : moins sensible qu'une moyenne à un ticket resté ouvert des semaines. */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }
        sort($values);
        $middle = intdiv(count($values), 2);
        return count($values) % 2 === 1
            ? (float) $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
