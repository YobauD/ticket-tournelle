<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Connexion unique à MySQL via PDO (patron Singleton).
 *
 * Toute requête passe par query(), donc par une requête préparée :
 * les valeurs ne sont jamais concaténées dans le SQL (protection contre les injections).
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** Renvoie la connexion, créée au premier appel seulement. */
    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $socket = Config::get('db.socket');
            $target = $socket
                ? 'unix_socket=' . $socket
                : sprintf('host=%s;port=%d', Config::get('db.host'), Config::get('db.port'));
            $dsn = sprintf('mysql:%s;dbname=%s;charset=utf8mb4', $target, Config::get('db.name'));

            try {
                self::$pdo = new PDO($dsn, Config::get('db.user'), Config::get('db.password'), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // une erreur SQL lève une exception
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // résultats en tableaux associatifs
                    PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées côté MySQL
                ]);
            } catch (PDOException $e) {
                // Message qui dit quoi vérifier (jamais le mot de passe). Affiché seulement si debug = true.
                throw new RuntimeException(sprintf(
                    'Connexion à MySQL impossible (%s, base %s, utilisateur %s) : %s. '
                    . 'Vérifiez que MySQL est démarré et la section « db » de config/config.local.php.',
                    $target,
                    Config::get('db.name'),
                    Config::get('db.user'),
                    $e->getMessage()
                ), 0, $e);
            }

            // NOW() et CURRENT_TIMESTAMP suivent le fuseau de l'application (heure d'été comprise),
            // quel que soit le réglage du serveur MySQL
            self::$pdo->exec('SET time_zone = ' . self::$pdo->quote(date('P')));
        }
        return self::$pdo;
    }

    /**
     * Prépare et exécute une requête.
     *
     * @param string $sql    Requête avec marqueurs (? ou :nom)
     * @param array  $params Valeurs liées aux marqueurs
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Exécute $callback dans une transaction : tout est validé, ou rien.
     * Utilisé dès qu'une action écrit dans plusieurs tables (ticket + historique).
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
