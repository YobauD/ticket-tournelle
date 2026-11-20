<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Comment
{
    /**
     * Échanges d'un ticket, du plus ancien au plus récent.
     * $withInternal = false pour le déclarant : les notes internes ne sortent jamais de la base.
     */
    public static function forTicket(int $ticketId, bool $withInternal): array
    {
        $sql = 'SELECT c.*, u.first_name, u.last_name, r.code AS role_code
                  FROM ticket_comments c
                  JOIN users u ON u.id = c.user_id
                  JOIN roles r ON r.id = u.role_id
                 WHERE c.ticket_id = ?' . ($withInternal ? '' : ' AND c.is_internal = 0') . '
                 ORDER BY c.created_at, c.id';
        return Database::query($sql, [$ticketId])->fetchAll();
    }

    public static function add(int $ticketId, int $userId, string $body, bool $isInternal = false): void
    {
        Database::query(
            'INSERT INTO ticket_comments (ticket_id, user_id, body, is_internal) VALUES (?, ?, ?, ?)',
            [$ticketId, $userId, $body, (int) $isInternal]
        );
    }
}
