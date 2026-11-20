<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Indicateurs de pilotage du tableau de bord statistique.
 *
 * Toutes les méthodes reçoivent le début de la période ($from, « AAAA-MM-JJ HH:MM:SS »).
 * Les durées sont des heures calendaires (nuits et week-ends compris), comme les délais
 * affichés sur les tickets.
 */
final class Stats
{
    /** Délai, en jours, dans lequel un nouveau ticket au même endroit et de même catégorie compte comme récurrent. */
    public const RECURRENCE_DAYS = 30;

    /** Nombre de tickets déclarés depuis $from. */
    public static function declared(string $from): int
    {
        return (int) Database::query('SELECT COUNT(*) FROM tickets WHERE created_at >= ?', [$from])->fetchColumn();
    }

    /** Tickets encore à traiter aujourd'hui (Nouveau, En cours, En attente), toutes dates confondues. */
    public static function openNow(): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM tickets t JOIN status s ON s.id = t.status_id
              WHERE s.code IN ('new', 'in_progress', 'pending')"
        )->fetchColumn();
    }

    /**
     * Tickets résolus depuis $from : durée de résolution en minutes et objectif de leur priorité.
     * Un ticket rouvert puis résolu à nouveau compte avec sa dernière résolution.
     */
    public static function resolutions(string $from): array
    {
        return Database::query(
            'SELECT TIMESTAMPDIFF(MINUTE, t.created_at, t.resolved_at) AS minutes,
                    p.code AS priority_code, p.sla_resolution_h
               FROM tickets t
               JOIN priorities p ON p.id = t.priority_id
              WHERE t.resolved_at IS NOT NULL AND t.resolved_at >= ?',
            [$from]
        )->fetchAll();
    }

    /** Durée moyenne, en minutes, entre la déclaration et la prise en charge (null si aucun ticket). */
    public static function averageTakeMinutes(string $from): ?float
    {
        $value = Database::query(
            'SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, taken_at)) FROM tickets
              WHERE taken_at IS NOT NULL AND taken_at >= ?',
            [$from]
        )->fetchColumn();
        return $value === null ? null : (float) $value;
    }

    /**
     * Taux de récurrence : part des tickets déclarés depuis $from pour lesquels un autre
     * ticket avait déjà été déclaré dans la même salle, dans la même catégorie, au cours
     * des RECURRENCE_DAYS jours précédents.
     *
     * @return array{total: int, recurrent: int}
     */
    public static function recurrence(string $from): array
    {
        $row = Database::query(
            'SELECT COUNT(*) AS total, COALESCE(SUM(r.is_recurrent), 0) AS recurrent
               FROM (
                     SELECT EXISTS (
                              SELECT 1 FROM tickets prev
                               WHERE prev.location_id = t.location_id
                                 AND prev.category_id = t.category_id
                                 AND prev.id < t.id
                                 AND prev.created_at >= DATE_SUB(t.created_at, INTERVAL ? DAY)
                            ) AS is_recurrent
                       FROM tickets t
                      WHERE t.created_at >= ?
                    ) r',
            [self::RECURRENCE_DAYS, $from]
        )->fetch();

        return ['total' => (int) $row['total'], 'recurrent' => (int) $row['recurrent']];
    }

    /**
     * Couples lieu + catégorie les plus signalés depuis $from (au moins 2 tickets).
     * Répond à la question de la MOA : quel matériel ou quelle salle pose le plus de problèmes ?
     */
    public static function hotspots(string $from, int $limit = 5): array
    {
        return Database::query(
            'SELECT l.name AS location_name, c.label_fr, c.label_en,
                    COUNT(*) AS ticket_count, MAX(t.created_at) AS last_at
               FROM tickets t
               JOIN locations  l ON l.id = t.location_id
               JOIN categories c ON c.id = t.category_id
              WHERE t.created_at >= ?
              GROUP BY l.id, c.id
             HAVING COUNT(*) >= 2
              ORDER BY ticket_count DESC, last_at DESC
              LIMIT ?',
            [$from, $limit]
        )->fetchAll();
    }

    /** Nombre de tickets par catégorie depuis $from. Une catégorie désactivée n'apparaît que si elle a des tickets. */
    public static function byCategory(string $from): array
    {
        return Database::query(
            'SELECT c.label_fr, c.label_en, COUNT(t.id) AS ticket_count
               FROM categories c
               LEFT JOIN tickets t ON t.category_id = c.id AND t.created_at >= ?
              GROUP BY c.id
             HAVING ticket_count > 0 OR MAX(c.is_active) = 1
              ORDER BY ticket_count DESC, c.id',
            [$from]
        )->fetchAll();
    }

    /** Nombre de tickets déclarés par jour depuis $from : ['2026-11-02' => 3, ...]. */
    public static function perDay(string $from): array
    {
        return Database::query(
            'SELECT DATE(created_at) AS day, COUNT(*) FROM tickets
              WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY day',
            [$from]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /** Date du premier ticket (pour la période « Depuis le début »), ou null s'il n'y en a aucun. */
    public static function firstTicketDate(): ?string
    {
        $value = Database::query('SELECT MIN(created_at) FROM tickets')->fetchColumn();
        return $value === null ? null : (string) $value;
    }
}
