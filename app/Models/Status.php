<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Status
{
    private static ?array $cache = null;

    /** Tous les statuts, indexés par code ('new', 'in_progress'...), dans l'ordre du cycle de vie. */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::query('SELECT * FROM status ORDER BY sort_order')->fetchAll() as $row) {
                self::$cache[$row['code']] = $row;
            }
        }
        return self::$cache;
    }

    public static function id(string $code): int
    {
        return (int) self::all()[$code]['id'];
    }
}
