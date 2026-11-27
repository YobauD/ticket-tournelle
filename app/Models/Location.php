<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

/**
 * Lieux : une salle (« A102 ») ou un bureau (« Bureau du proviseur »).
 *
 * Le déclarant saisit le lieu librement. Pour que les statistiques restent justes, une
 * saisie est rapprochée d'un lieu existant quand elle n'en diffère que par les majuscules,
 * les accents, les espaces, la ponctuation ou le mot « salle » : « salle a102 », « A 102 »
 * et « A102 » désignent le même lieu (voir key()).
 *
 * Un lieu inconnu est créé « à vérifier » (is_verified = 0) : il est enregistré sur le
 * ticket, mais n'est proposé aux autres déclarants qu'une fois validé par un administrateur.
 * Un lieu n'est jamais supprimé, sauf quand il est fusionné dans un autre.
 */
final class Location
{
    /** Longueur d'un nom de lieu, en caractères. */
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 80;

    /** Lieux proposés pendant la saisie : actifs et validés. */
    public static function suggestions(): array
    {
        return Database::query(
            'SELECT id, name FROM locations WHERE is_active = 1 AND is_verified = 1 ORDER BY name'
        )->fetchAll();
    }

    /** Tous les lieux, avec le nombre de tickets de chacun (écran d'administration). */
    public static function allWithCounts(): array
    {
        return Database::query(
            'SELECT l.*, COUNT(t.id) AS ticket_count
               FROM locations l
               LEFT JOIN tickets t ON t.location_id = l.id
              GROUP BY l.id
              ORDER BY l.is_verified ASC, l.is_active DESC, l.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM locations WHERE id = ?', [$id])->fetch() ?: null;
    }

    /** Lieux saisis par des déclarants, en attente de validation. */
    public static function countUnverified(): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM locations WHERE is_verified = 0 AND is_active = 1'
        )->fetchColumn();
    }

    // ------------------------------------------------------------------
    //  Saisie libre
    // ------------------------------------------------------------------

    /**
     * Nom propre à enregistrer : espaces en trop retirés ; un numéro de salle est mis
     * en majuscules, sans le mot « salle » (« salle a102 » donne « A102 ») ; un autre nom
     * prend une majuscule initiale (« bureau du proviseur » donne « Bureau du proviseur »).
     */
    public static function clean(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        // « salle b14 » ou « B 14 » : numéro de salle seul, en majuscules
        $bare = (string) preg_replace('/^salle\s+/iu', '', $name);
        if (preg_match('/^[a-z]{0,3}[ -]?\d{1,4}[a-z]?$/iu', $bare)) {
            return mb_strtoupper((string) preg_replace('/[ -]/', '', $bare));
        }
        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /**
     * Clé de comparaison : deux saisies de même clé désignent le même lieu.
     * Minuscules, sans accents, sans espaces ni ponctuation, sans le mot « salle » initial :
     * « Salle A-102 », « a102 » et « A 102 » donnent toutes « a102 ».
     */
    public static function key(string $name): string
    {
        $key = trim((string) preg_replace('/[^a-z0-9]+/', ' ', fold_accents($name)));
        $key = (string) preg_replace('/^(salle|room)\s+/', '', $key);
        return str_replace(' ', '', $key);
    }

    /** Lieu existant correspondant à une saisie (actif ou non), ou null. */
    public static function match(string $name): ?array
    {
        $key = self::key($name);
        if ($key === '') {
            return null;
        }
        // Un lycée compte quelques centaines de lieux au plus : la comparaison se fait en PHP,
        // où la clé est calculée exactement de la même façon pour tous
        foreach (Database::query('SELECT * FROM locations')->fetchAll() as $location) {
            if (self::key($location['name']) === $key) {
                return $location;
            }
        }
        return null;
    }

    /**
     * Lieu d'un ticket à partir de la saisie du déclarant : le lieu existant correspondant,
     * sinon un nouveau lieu « à vérifier ».
     *
     * @return array{id: int, name: string, created: bool, active: bool}
     */
    public static function resolve(string $typed): array
    {
        $existing = self::match($typed);
        if ($existing !== null) {
            return [
                'id'      => (int) $existing['id'],
                'name'    => $existing['name'],
                'created' => false,
                'active'  => (int) $existing['is_active'] === 1,
            ];
        }

        $name = self::clean($typed);
        try {
            Database::query('INSERT INTO locations (name, is_verified) VALUES (?, 0)', [$name]);
            return ['id' => Database::lastInsertId(), 'name' => $name, 'created' => true, 'active' => true];
        } catch (PDOException $e) {
            // Deux déclarations simultanées du même nouveau lieu : la seconde reprend le lieu créé
            $again = self::match($typed);
            if ($again === null) {
                throw $e;
            }
            return ['id' => (int) $again['id'], 'name' => $again['name'], 'created' => false, 'active' => (int) $again['is_active'] === 1];
        }
    }

    // ------------------------------------------------------------------
    //  Administration
    // ------------------------------------------------------------------

    /** Un autre lieu porte-t-il déjà ce nom (au sens de key()) ? */
    public static function nameTaken(string $name, ?int $exceptId = null): bool
    {
        $existing = self::match($name);
        return $existing !== null && (int) $existing['id'] !== ($exceptId ?? 0);
    }

    public static function create(string $name): int
    {
        Database::query('INSERT INTO locations (name, is_verified) VALUES (?, 1)', [self::clean($name)]);
        return Database::lastInsertId();
    }

    /** Enregistrer un lieu depuis l'administration le valide. */
    public static function update(int $id, string $name, bool $isActive): void
    {
        Database::query(
            'UPDATE locations SET name = ?, is_active = ?, is_verified = 1 WHERE id = ?',
            [self::clean($name), (int) $isActive, $id]
        );
    }

    /**
     * Fusionne $sourceId dans $targetId : les tickets du premier passent au second,
     * puis le premier est supprimé. Les statistiques regroupent alors bien les deux saisies.
     *
     * @return int nombre de tickets déplacés
     */
    public static function merge(int $sourceId, int $targetId): int
    {
        return Database::transaction(static function () use ($sourceId, $targetId): int {
            $moved = Database::query(
                'UPDATE tickets SET location_id = ? WHERE location_id = ?',
                [$targetId, $sourceId]
            )->rowCount();
            Database::query('DELETE FROM locations WHERE id = ?', [$sourceId]);
            return $moved;
        });
    }
}
