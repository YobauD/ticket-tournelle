<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Catégories d'incidents.
 * Une catégorie n'est jamais supprimée : elle est désactivée, pour que les
 * anciens tickets et les statistiques gardent leur libellé.
 */
final class Category
{
    /** Catégories proposées dans le formulaire de déclaration. */
    public static function active(): array
    {
        return Database::query('SELECT * FROM categories WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    public static function isActive(int $id): bool
    {
        return (bool) Database::query('SELECT 1 FROM categories WHERE id = ? AND is_active = 1', [$id])->fetchColumn();
    }

    /** Toutes les catégories, avec le nombre de tickets de chacune (écran d'administration). */
    public static function allWithCounts(): array
    {
        return Database::query(
            'SELECT c.*, COUNT(t.id) AS ticket_count
               FROM categories c
               LEFT JOIN tickets t ON t.category_id = c.id
              GROUP BY c.id
              ORDER BY c.is_active DESC, c.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM categories WHERE id = ?', [$id])->fetch() ?: null;
    }

    /**
     * Libellé déjà utilisé par une autre catégorie ? Renvoie 'fr', 'en' ou null.
     * La comparaison ignore majuscules et accents (collation utf8mb4_unicode_ci).
     */
    public static function labelTaken(string $labelFr, string $labelEn, ?int $exceptId = null): ?string
    {
        foreach (['fr' => $labelFr, 'en' => $labelEn] as $lang => $label) {
            $taken = Database::query(
                // $lang vient des clés fixes du tableau ci-dessus, jamais de la saisie
                "SELECT 1 FROM categories WHERE label_$lang = ? AND id <> ?",
                [$label, $exceptId ?? 0]
            )->fetchColumn();
            if ($taken) {
                return $lang;
            }
        }
        return null;
    }

    public static function create(string $labelFr, string $labelEn): int
    {
        Database::query('INSERT INTO categories (label_fr, label_en) VALUES (?, ?)', [$labelFr, $labelEn]);
        return Database::lastInsertId();
    }

    public static function update(int $id, string $labelFr, string $labelEn, bool $isActive): void
    {
        Database::query(
            'UPDATE categories SET label_fr = ?, label_en = ?, is_active = ? WHERE id = ?',
            [$labelFr, $labelEn, (int) $isActive, $id]
        );
    }

    public static function countActive(): int
    {
        return (int) Database::query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
    }
}
