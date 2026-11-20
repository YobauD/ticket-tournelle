<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Priority
{
    public static function all(): array
    {
        return Database::query('SELECT * FROM priorities ORDER BY sort_order')->fetchAll();
    }

    public static function exists(int $id): bool
    {
        return (bool) Database::query('SELECT 1 FROM priorities WHERE id = ?', [$id])->fetchColumn();
    }
}
