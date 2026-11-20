<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Database;
use RuntimeException;

/**
 * Pièces jointes des tickets.
 *
 * Mesures de sécurité appliquées à tout fichier déposé :
 *  - taille limitée ;
 *  - type vérifié sur le CONTENU du fichier (finfo), pas sur son nom ni sur ce qu'annonce le navigateur ;
 *  - liste blanche de types (images, PDF) : aucun fichier exécutable ;
 *  - stockage hors de la racine web, sous un nom aléatoire : jamais exécuté ni servi directement par Apache.
 */
final class Attachment
{
    /**
     * Contrôle un fichier reçu via $_FILES.
     *
     * @return array|null [type MIME, extension], ou null si aucun fichier n'a été joint
     * @throws RuntimeException dont le message est une clé de traduction
     */
    public static function validate(?array $file): ?array
    {
        if ($file === null || !isset($file['error']) || is_array($file['error'])
            || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || $file['size'] > Config::get('uploads.max_size')) {
            throw new RuntimeException('attachment.too_large');
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('attachment.failed');
        }

        $mime    = self::detectMime($file['tmp_name']);
        $allowed = Config::get('uploads.allowed');
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('attachment.bad_type');
        }
        return [$mime, $allowed[$mime]];
    }

    /**
     * Type réel du fichier, lu dans son contenu.
     * Sans l'extension fileinfo (absente de certaines installations MAMP sous Windows),
     * on lit les premiers octets, qui identifient le format quel que soit le nom du fichier.
     */
    private static function detectMime(string $path): string
    {
        if (class_exists(\finfo::class)) {
            return (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        }

        $head = (string) file_get_contents($path, false, null, 0, 8);
        return match (true) {
            str_starts_with($head, "\xFF\xD8\xFF")      => 'image/jpeg',
            str_starts_with($head, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($head, '%PDF-')             => 'application/pdf',
            default                                     => 'application/octet-stream',
        };
    }

    /** Déplace un fichier déjà validé dans le dossier de stockage et l'enregistre en base. */
    public static function store(array $file, string $mime, string $extension, int $ticketId, int $userId): void
    {
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], Config::get('uploads.dir') . '/' . $storedName)) {
            throw new RuntimeException('attachment.failed');
        }

        Database::query(
            'INSERT INTO ticket_attachments (ticket_id, user_id, original_name, stored_name, mime_type, size_bytes)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$ticketId, $userId, mb_substr(basename($file['name']), 0, 190), $storedName, $mime, (int) $file['size']]
        );
    }

    public static function forTicket(int $ticketId): array
    {
        return Database::query('SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY id', [$ticketId])->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::query('SELECT * FROM ticket_attachments WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function path(array $attachment): string
    {
        return Config::get('uploads.dir') . '/' . $attachment['stored_name'];
    }
}
