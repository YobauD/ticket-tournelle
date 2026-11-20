<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Historique d'un ticket (table ticket_history) : qui a fait quoi, et quand.
 */
final class History
{
    public static function forTicket(int $ticketId): array
    {
        return Database::query(
            'SELECT h.*, u.first_name, u.last_name,
                    o.label_fr AS old_fr, o.label_en AS old_en,
                    n.label_fr AS new_fr, n.label_en AS new_en
               FROM ticket_history h
               LEFT JOIN users  u ON u.id = h.user_id
               LEFT JOIN status o ON o.id = h.old_status_id
               LEFT JOIN status n ON n.id = h.new_status_id
              WHERE h.ticket_id = ?
              ORDER BY h.created_at, h.id',
            [$ticketId]
        )->fetchAll();
    }

    /**
     * @param string   $event  created | status | assigned | escalated | auto_closed
     * @param int|null $userId null = action automatique du système
     */
    public static function add(
        int $ticketId,
        ?int $userId,
        string $event,
        ?int $oldStatusId = null,
        ?int $newStatusId = null,
        ?string $note = null
    ): void {
        Database::query(
            'INSERT INTO ticket_history (ticket_id, user_id, event, old_status_id, new_status_id, note)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$ticketId, $userId, $event, $oldStatusId, $newStatusId, $note]
        );
    }
}
