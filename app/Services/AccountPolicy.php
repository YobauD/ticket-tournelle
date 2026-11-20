<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Règles d'habilitation sur les comptes (contrainte C5 : gestion des comptes
 * administrateurs selon les recommandations en vigueur, principe du moindre privilège).
 *
 *  - Un administrateur gère les enseignants, personnels et techniciens.
 *  - Seul le superadministrateur crée, modifie, désactive un administrateur ou
 *    réinitialise son mot de passe.
 *  - Un compte superadministrateur ne se gère jamais depuis le site : il est créé ou
 *    réinitialisé sur le serveur (scripts/superadmin.php). Un compte superadministrateur
 *    volé ne peut donc pas en créer d'autres, et un mot de passe perdu se récupère
 *    avec un accès au serveur.
 *  - Personne ne modifie son propre rôle ni ne désactive son propre compte depuis
 *    l'administration (son mot de passe se change dans « Mon compte »).
 *
 * Contrôleurs ET vues appellent ces méthodes : l'écran n'affiche que ce que le serveur
 * acceptera, et le serveur refuse une requête fabriquée à la main.
 */
final class AccountPolicy
{
    /** Rôles qu'un compte peut attribuer, à la création ou à la modification d'un compte. */
    public static function assignableRoles(array $actor): array
    {
        return match ($actor['role_code']) {
            'superadmin' => ['user', 'technician', 'admin'],
            'admin'      => ['user', 'technician'],
            default      => [],
        };
    }

    public static function canAssign(array $actor, string $roleCode): bool
    {
        return in_array($roleCode, self::assignableRoles($actor), true);
    }

    /**
     * Le compte $actor peut-il modifier le compte $target (rôle, niveau, activation,
     * mot de passe provisoire) depuis l'administration ?
     */
    public static function canManage(array $actor, array $target): bool
    {
        if ((int) $actor['id'] === (int) $target['id']) {
            return false;
        }
        return match ($target['role_code']) {
            'superadmin'          => false,
            'admin'               => $actor['role_code'] === 'superadmin',
            'user', 'technician'  => User::hasAdminRights($actor),
            default               => false,
        };
    }

    /**
     * Le compte $actor voit-il (et reçoit-il par e-mail) la demande de nouveau mot de passe
     * faite pour $target depuis la page de connexion ?
     * Oui s'il peut réinitialiser ce mot de passe. Un superadministrateur, lui, ne se gère pas
     * depuis le site : tous les autres administrateurs sont prévenus (sinon, dans un lycée qui
     * n'a qu'un superadministrateur, personne ne le serait). Ils ne peuvent rien modifier :
     * l'écran leur indique que la réinitialisation se fait sur le serveur (scripts/superadmin.php).
     */
    public static function seesResetRequest(array $actor, array $target): bool
    {
        if (self::canManage($actor, $target)) {
            return true;
        }
        return $target['role_code'] === 'superadmin'
            && User::hasAdminRights($actor)
            && (int) $actor['id'] !== (int) $target['id'];
    }

    /**
     * Pourquoi un compte n'est pas modifiable, pour l'expliquer à l'écran.
     * @return string|null clé de traduction, ou null si le compte est modifiable
     */
    public static function lockReason(array $actor, array $target): ?string
    {
        if (self::canManage($actor, $target)) {
            return null;
        }
        if ((int) $actor['id'] === (int) $target['id']) {
            return 'admin.lock.self';
        }
        return $target['role_code'] === 'superadmin' ? 'admin.lock.server' : 'admin.lock.superadmin';
    }
}
