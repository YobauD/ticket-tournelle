<?php
/**
 * Table de routage.
 *
 * Chaque route : [méthode HTTP, chemin, [Contrôleur, action], rôles autorisés]
 *   - rôles = null            : route publique
 *   - rôles = ['admin', ...]  : connexion obligatoire + rôle requis (contrôle RBAC fait par le routeur)
 * Les segments {id} n'acceptent que des entiers.
 * Toutes les routes POST sont protégées par un jeton CSRF vérifié par le routeur.
 */

use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\HelpController;
use App\Controllers\LangController;
use App\Controllers\ReferenceController;
use App\Controllers\StatsController;
use App\Controllers\TechnicianController;
use App\Controllers\TicketController;

const ALL   = ['user', 'technician', 'admin', 'superadmin'];
const STAFF = ['technician', 'admin', 'superadmin'];
const ADMIN = ['admin', 'superadmin'];   // ce que chacun peut faire sur les comptes : App\Services\AccountPolicy

return [
    // --- Authentification et langue --------------------------------------
    ['GET',  '/',       [AuthController::class, 'home'],      ALL],
    ['GET',  '/login',  [AuthController::class, 'showLogin'], null],
    ['POST', '/login',  [AuthController::class, 'login'],     null],
    ['POST', '/logout', [AuthController::class, 'logout'],    ALL],
    ['GET',  '/password/forgot', [AuthController::class, 'showForgot'], null],   // lien envoyé à l'adresse confirmée
    ['POST', '/password/forgot', [AuthController::class, 'forgot'],     null],
    ['GET',  '/password/reset',  [AuthController::class, 'showReset'],  null],   // formulaire ouvert depuis le lien
    ['POST', '/password/reset',  [AuthController::class, 'reset'],      null],
    ['GET',  '/password/admin',  [AuthController::class, 'showAdminRequest'], null],   // secours : demande à un administrateur
    ['POST', '/password/admin',  [AuthController::class, 'adminRequest'],     null],
    ['POST', '/lang',   [LangController::class, 'switch'],    null],
    ['GET',  '/help',   [HelpController::class, 'index'],     ALL],    // aide : réservée aux personnes connectées
    ['POST', '/help/ask', [HelpController::class, 'ask'],     ALL],    // assistant d'aide (réponses JSON)
    ['GET',  '/account',          [AccountController::class, 'show'],           ALL],
    ['POST', '/account',          [AccountController::class, 'updatePassword'], ALL],
    ['POST', '/account/email',    [AccountController::class, 'updateEmail'],    ALL],
    ['POST', '/account/email/resend', [AccountController::class, 'resendConfirmation'], ALL],
    ['GET',  '/account/email/confirm', [AccountController::class, 'confirmEmail'], null],   // lien reçu par e-mail

    // --- Espace déclarant --------------------------------------------------
    ['GET',  '/tickets',               [TicketController::class, 'index'],      ALL],
    ['GET',  '/tickets/new',           [TicketController::class, 'create'],     ALL],
    ['POST', '/tickets',               [TicketController::class, 'store'],      ALL],
    ['GET',  '/tickets/{id}',          [TicketController::class, 'show'],       ALL],
    ['POST', '/tickets/{id}/comments', [TicketController::class, 'comment'],    ALL],
    ['POST', '/tickets/{id}/close',    [TicketController::class, 'close'],      ALL],
    ['POST', '/tickets/{id}/reopen',   [TicketController::class, 'reopen'],     ALL],
    ['GET',  '/attachments/{id}',      [TicketController::class, 'attachment'], ALL],

    // --- Espace technicien -------------------------------------------------
    ['GET',  '/queue',                 [TechnicianController::class, 'queue'],    STAFF],
    ['POST', '/tickets/{id}/take',     [TechnicianController::class, 'take'],     STAFF],
    ['POST', '/tickets/{id}/status',   [TechnicianController::class, 'status'],   STAFF],
    ['POST', '/tickets/{id}/escalate', [TechnicianController::class, 'escalate'], STAFF],

    // --- Administration ----------------------------------------------------
    ['GET',  '/admin/users',               [AdminController::class, 'users'],         ADMIN],
    ['POST', '/admin/users',               [AdminController::class, 'storeUser'],     ADMIN],
    ['POST', '/admin/users/{id}',          [AdminController::class, 'updateUser'],    ADMIN],
    ['POST', '/admin/users/{id}/password', [AdminController::class, 'resetPassword'], ADMIN],
    ['POST', '/admin/users/{id}/identity', [AdminController::class, 'updateIdentity'], ADMIN],   // identifiant et e-mail
    ['POST', '/admin/password-requests/{id}/dismiss', [AdminController::class, 'dismissRequest'], ADMIN],

    ['GET',  '/admin/categories',      [ReferenceController::class, 'categories'],     ADMIN],
    ['POST', '/admin/categories',      [ReferenceController::class, 'storeCategory'],  ADMIN],
    ['POST', '/admin/categories/{id}', [ReferenceController::class, 'updateCategory'], ADMIN],
    ['GET',  '/admin/locations',       [ReferenceController::class, 'locations'],      ADMIN],
    ['POST', '/admin/locations',       [ReferenceController::class, 'storeLocation'],  ADMIN],
    ['POST', '/admin/locations/{id}',  [ReferenceController::class, 'updateLocation'], ADMIN],
    ['POST', '/admin/locations/{id}/merge', [ReferenceController::class, 'mergeLocation'], ADMIN],

    ['GET',  '/admin/titles',          [ReferenceController::class, 'jobTitles'],      ADMIN],
    ['POST', '/admin/titles',          [ReferenceController::class, 'storeJobTitle'],  ADMIN],
    ['POST', '/admin/titles/{id}',     [ReferenceController::class, 'updateJobTitle'], ADMIN],

    ['GET',  '/admin/logs',            [AuditController::class, 'index'],              ADMIN],

    // --- Pilotage ------------------------------------------------------------
    // Pour ouvrir les statistiques à un rôle « Direction », l'ajouter ici
    ['GET',  '/stats',                 [StatsController::class, 'index'],              ADMIN],
];
