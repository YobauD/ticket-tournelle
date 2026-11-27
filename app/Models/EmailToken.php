<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Liens envoyés par e-mail : « choisir un nouveau mot de passe » (reset) et confirmation
 * de l'adresse de récupération (verify).
 *
 * Le jeton est un nombre aléatoire de 256 bits, envoyé dans le lien. La base n'en garde que
 * l'empreinte SHA-256 : quelqu'un qui lirait la table n'aurait aucun lien utilisable. Un lien
 * ne sert qu'une fois, expire, et un nouveau lien annule le précédent de même type.
 */
final class EmailToken
{
    public const RESET  = 'reset';
    public const VERIFY = 'verify';

    /**
     * Crée un lien et renvoie le jeton en clair, à mettre dans l'e-mail (il n'est stocké nulle part).
     * Les liens encore valides du même type pour ce compte sont annulés.
     */
    public static function create(int $userId, string $purpose, string $email, int $minutes): string
    {
        self::invalidate($userId, $purpose);
        $token = bin2hex(random_bytes(32));
        Database::query(
            'INSERT INTO email_tokens (user_id, purpose, email, token_hash, expires_at, ip_address)
             VALUES (?, ?, ?, ?, NOW() + INTERVAL ? MINUTE, ?)',
            [$userId, $purpose, mb_strtolower($email), self::hash($token), $minutes, $_SERVER['REMOTE_ADDR'] ?? null]
        );
        return $token;
    }

    /**
     * Lien valide (bon type, ni utilisé ni expiré, compte actif), avec le compte concerné.
     * Les colonnes du compte sont celles de User::find ; celles du lien sont préfixées token_.
     */
    public static function findValid(string $token, string $purpose): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = Database::query(
            'SELECT t.id AS token_id, t.email AS token_email, t.user_id
               FROM email_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_hash = ? AND t.purpose = ? AND t.used_at IS NULL
                AND t.expires_at > NOW() AND u.is_active = 1',
            [self::hash($token), $purpose]
        )->fetch();
        if (!$row) {
            return null;
        }
        $user = User::find((int) $row['user_id']);
        return $user === null ? null : $user + ['token_id' => (int) $row['token_id'], 'token_email' => $row['token_email']];
    }

    /**
     * Marque le lien comme utilisé. Renvoie faux s'il l'était déjà : deux envois simultanés
     * du même formulaire ne peuvent pas l'utiliser deux fois.
     */
    public static function consume(int $tokenId): bool
    {
        return Database::query(
            'UPDATE email_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND expires_at > NOW()',
            [$tokenId]
        )->rowCount() === 1;
    }

    /** Annule les liens encore valides d'un type pour un compte. */
    public static function invalidate(int $userId, string $purpose): void
    {
        Database::query(
            'UPDATE email_tokens SET used_at = NOW() WHERE user_id = ? AND purpose = ? AND used_at IS NULL',
            [$userId, $purpose]
        );
    }

    /** Liens envoyés à ce compte ces $minutes dernières minutes (limite d'envoi). */
    public static function countRecent(int $userId, string $purpose, int $minutes): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) FROM email_tokens WHERE user_id = ? AND purpose = ? AND created_at > NOW() - INTERVAL ? MINUTE',
            [$userId, $purpose, $minutes]
        )->fetchColumn();
    }

    /** Adresse en attente de confirmation pour ce compte, s'il y en a une. */
    public static function pendingVerification(int $userId): ?string
    {
        $email = Database::query(
            "SELECT email FROM email_tokens
              WHERE user_id = ? AND purpose = 'verify' AND used_at IS NULL AND expires_at > NOW()
              ORDER BY id DESC LIMIT 1",
            [$userId]
        )->fetchColumn();
        return $email === false ? null : $email;
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
