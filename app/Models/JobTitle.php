<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Fonctions des personnels : Professeur, Direction, Vie scolaire…
 *
 * Indépendantes du rôle : le rôle fixe les droits sur le portail, la fonction dit ce que
 * la personne fait au lycée. Libellés dans les deux langues de l'interface (contrainte C2).
 * Une fonction n'est jamais supprimée : désactivée, elle n'est plus proposée pour un compte,
 * mais les comptes qui l'ont la gardent.
 */
final class JobTitle
{
    /** Fonctions proposées lors de la création ou de la modification d'un compte. */
    public static function active(): array
    {
        return Database::query('SELECT * FROM job_titles WHERE is_active = 1 ORDER BY id')->fetchAll();
    }

    /** Toutes les fonctions, avec le nombre de comptes de chacune (écran d'administration). */
    public static function allWithCounts(): array
    {
        return Database::query(
            'SELECT j.*, COUNT(u.id) AS user_count
               FROM job_titles j
               LEFT JOIN users u ON u.job_title_id = j.id
              GROUP BY j.id
              ORDER BY j.is_active DESC, j.id'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM job_titles WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function isActive(int $id): bool
    {
        return (bool) Database::query('SELECT 1 FROM job_titles WHERE id = ? AND is_active = 1', [$id])->fetchColumn();
    }

    /**
     * Libellé déjà utilisé par une autre fonction ? Renvoie 'fr', 'en' ou null.
     * La comparaison ignore majuscules et accents (collation utf8mb4_unicode_ci).
     */
    public static function labelTaken(string $labelFr, string $labelEn, ?int $exceptId = null): ?string
    {
        foreach (['fr' => $labelFr, 'en' => $labelEn] as $lang => $label) {
            // $lang vient des clés fixes du tableau ci-dessus, jamais de la saisie
            $taken = Database::query(
                "SELECT 1 FROM job_titles WHERE label_$lang = ? AND id <> ?",
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
        Database::query('INSERT INTO job_titles (label_fr, label_en) VALUES (?, ?)', [$labelFr, $labelEn]);
        return Database::lastInsertId();
    }

    public static function update(int $id, string $labelFr, string $labelEn, bool $isActive): void
    {
        Database::query(
            'UPDATE job_titles SET label_fr = ?, label_en = ?, is_active = ? WHERE id = ?',
            [$labelFr, $labelEn, (int) $isActive, $id]
        );
    }
}
