<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Lang;
use App\Core\Session;
use App\Core\View;
use App\Models\JobTitle;
use App\Models\Log;
use App\Models\PasswordRequest;
use App\Models\User;
use App\Services\AccountPolicy;

/**
 * Administration des comptes et des habilitations.
 *
 * Qui peut faire quoi : App\Services\AccountPolicy (un administrateur gère les enseignants
 * et les techniciens ; seul le superadministrateur gère les administrateurs).
 * Chaque action sensible est inscrite au journal d'audit, y compris les tentatives refusées.
 */
final class AdminController extends Controller
{
    public function users(): void
    {
        $this->renderUsers([], []);
    }

    /** Crée un compte nominatif avec un mot de passe provisoire. */
    public function storeUser(): void
    {
        $old = [
            'last_name'  => $this->input('last_name'),
            'first_name' => $this->input('first_name'),
            'username'   => mb_strtolower($this->input('username')),
            'role_id'    => (int) $this->input('role_id'),
            'tech_level' => (int) $this->input('tech_level'),
            'lang'       => $this->input('lang', 'fr'),
            'job_title_id' => (int) $this->input('job_title_id'),
        ];
        // Mot de passe provisoire tiré au hasard par le serveur, affiché une seule fois
        $password = Auth::temporaryPassword();
        $roles    = array_column(User::roles(), null, 'id');
        $errors   = [];

        if ($old['last_name'] === '' || mb_strlen($old['last_name']) > 80) {
            $errors['last_name'] = t('admin.error.name');
        }
        if ($old['first_name'] === '' || mb_strlen($old['first_name']) > 80) {
            $errors['first_name'] = t('admin.error.name');
        }
        // Identifiant de connexion : proposé (prenom.nom) s'il est laissé vide
        if ($old['username'] === '' && !isset($errors['last_name']) && !isset($errors['first_name'])) {
            $old['username'] = User::suggestUsername($old['first_name'], $old['last_name']);
        } elseif ($old['username'] !== '' && !preg_match(User::USERNAME_PATTERN, $old['username'])) {
            $errors['username'] = t('admin.error.username');
        } elseif ($old['username'] !== '' && User::usernameTaken($old['username'])) {
            $errors['username'] = t('admin.error.username_taken');
        }
        // Pas d'adresse e-mail : la personne la choisit et la confirme à sa première connexion
        if (!isset($roles[$old['role_id']])) {
            $errors['role_id'] = t('admin.error.role');
        } elseif (!AccountPolicy::canAssign(Auth::user(), $roles[$old['role_id']]['code'])) {
            // Le menu ne propose pas ce rôle : la requête a été fabriquée à la main
            $errors['role_id'] = t('admin.error.role_forbidden');
            Log::write('permission_denied', 'login:' . mb_substr($old['username'], 0, 80), 'create role=' . $roles[$old['role_id']]['code']);
        }
        // Fonction facultative (0 = non renseignée), mais si elle est donnée, elle doit être active
        if ($old['job_title_id'] !== 0 && !JobTitle::isActive($old['job_title_id'])) {
            $errors['job_title_id'] = t('admin.error.job_title');
        }
        if (!Lang::isSupported($old['lang'])) {
            $old['lang'] = Config::get('lang.default');
        }

        if ($errors) {
            http_response_code(422);
            $this->renderUsers($old, $errors);
            return;
        }

        $role = $roles[$old['role_id']]['code'];
        $id   = User::create(
            $old['role_id'],
            $old['username'],
            $old['last_name'],
            $old['first_name'],
            null,
            Auth::hash($password),
            $this->levelFor($role, $old['tech_level']),
            $old['lang'],
            $old['job_title_id'] ?: null
        );
        $title = $old['job_title_id'] ? JobTitle::find($old['job_title_id']) : null;
        Log::write('user_created', 'user:' . $id, 'identifiant=' . $old['username'] . ', role=' . $role . ($title ? ', fonction=' . $title['label_fr'] : ''));

        Session::flash('success', t('admin.user_created', ['username' => $old['username'], 'password' => $password]));
        $this->redirect('/admin/users');
    }

    /** Modifie le rôle, le niveau de support et l'état (actif / désactivé) d'un compte. */
    public function updateUser(int $id): void
    {
        $user   = User::find($id) ?? View::abort(404);
        $roles  = array_column(User::roles(), null, 'id');
        $roleId = (int) $this->input('role_id');
        $active = $this->input('is_active') === '1';

        if (!isset($roles[$roleId])) {
            View::abort(403);
        }
        $newRole = $roles[$roleId]['code'];
        $level   = $this->levelFor($newRole, (int) $this->input('tech_level'));

        // Le compte doit être modifiable par la personne connectée, et le nouveau rôle attribuable
        $this->authorize($user, 'update role=' . $newRole);
        if (!AccountPolicy::canAssign(Auth::user(), $newRole)) {
            $this->deny($user, 'update role=' . $newRole);
        }

        // Fonction : 0 = non renseignée ; sinon une fonction active, ou celle que le compte a déjà
        // (une fonction désactivée reste sur les comptes qui l'ont)
        $titleId    = (int) $this->input('job_title_id') ?: null;
        $oldTitleId = $user['job_title_id'] === null ? null : (int) $user['job_title_id'];
        if ($titleId !== null && $titleId !== $oldTitleId && !JobTitle::isActive($titleId)) {
            Session::flash('error', t('admin.error.job_title'));
            $this->redirect('/admin/users');
        }

        User::updateAccess($id, $roleId, $level, $active);
        if ($titleId !== $oldTitleId) {
            User::setJobTitle($id, $titleId);
            $newTitle = $titleId !== null ? JobTitle::find($titleId) : null;
            Log::write('job_title_changed', 'user:' . $id, ($user['job_title_fr'] ?? '-') . ' -> ' . ($newTitle['label_fr'] ?? '-'));
        }

        // Une ligne de journal par changement réel
        if ($newRole !== $user['role_code']) {
            Log::write('role_changed', 'user:' . $id, $user['role_code'] . ' -> ' . $newRole);
        }
        if ($level !== ($user['tech_level'] === null ? null : (int) $user['tech_level'])) {
            Log::write('level_changed', 'user:' . $id, 'N' . ($user['tech_level'] ?? '-') . ' -> N' . ($level ?? '-'));
        }
        if ($active !== ((int) $user['is_active'] === 1)) {
            Log::write($active ? 'user_enabled' : 'user_disabled', 'user:' . $id);
        }

        Session::flash('success', t('admin.user_updated', ['name' => person($user)]));
        $this->redirect('/admin/users');
    }

    /** Attribue un nouveau mot de passe provisoire (et lève un éventuel verrouillage). */
    /**
     * Corrige l'identifiant de connexion d'un compte (faute de frappe, changement de nom).
     * Mêmes droits que pour les autres modifications du compte. La personne garde ses tickets,
     * son mot de passe et son adresse e-mail ; si elle est connectée, sa session continue.
     * L'adresse e-mail, elle, n'est modifiable que par la personne (Mon compte).
     */
    public function updateIdentity(int $id): void
    {
        $user = User::find($id) ?? View::abort(404);
        $this->authorize($user, 'identity update');

        $username = mb_strtolower($this->input('username'));
        $name     = person($user);

        $error = match (true) {
            !preg_match(User::USERNAME_PATTERN, $username) => t('admin.error.username'),
            User::usernameTaken($username, $id)             => t('admin.error.username_taken'),
            default                                         => null,
        };
        if ($error !== null) {
            Session::flash('error', t('admin.identity_error', ['name' => $name, 'error' => $error]));
            $this->redirect('/admin/users');
        }

        if ($username === $user['username']) {
            Session::flash('info', t('admin.identity_unchanged', ['name' => $name]));
            $this->redirect('/admin/users');
        }

        User::setUsername($id, $username);
        Log::write('identity_changed', 'user:' . $id, 'identifiant ' . $user['username'] . ' -> ' . $username);

        Session::flash('success', t('admin.identity_updated', ['name' => $name, 'username' => $username]));
        $this->redirect('/admin/users');
    }

    public function resetPassword(int $id): void
    {
        $user = User::find($id) ?? View::abort(404);
        $this->authorize($user, 'password reset');

        // Tiré au hasard par le serveur ; les sessions ouvertes du compte sont fermées
        $password = Auth::temporaryPassword();
        User::setPassword($id, Auth::hash($password), true);
        $answered = PasswordRequest::closeFor($id, 'done', Auth::id());   // demande faite depuis la page de connexion
        Log::write('password_reset', 'user:' . $id, $answered ? 'suite à une demande « mot de passe oublié »' : null);

        Session::flash('success', t('admin.password_reset', ['name' => person($user), 'password' => $password]));
        $this->redirect('/admin/users');
    }

    // ------------------------------------------------------------------

    /** Interrompt l'action si le compte visé n'est pas modifiable par la personne connectée. */
    private function authorize(array $target, string $attempt): void
    {
        if (!AccountPolicy::canManage(Auth::user(), $target)) {
            $this->deny($target, $attempt);
        }
    }

    /**
     * Refus : inscrit au journal (une tentative sur un compte protégé est un signal de sécurité),
     * puis retour à la liste avec un message.
     */
    private function deny(array $target, string $attempt): never
    {
        Log::write('permission_denied', 'user:' . $target['id'], $attempt);
        Session::flash('error', t('admin.error.forbidden'));
        $this->redirect('/admin/users');
    }

    /** Seul un technicien a un niveau de support (1 à 3, 1 par défaut). */
    private function levelFor(string $roleCode, int $level): ?int
    {
        if ($roleCode !== 'technician') {
            return null;
        }
        return max(1, min(3, $level ?: 1));
    }

    /**
     * Ignore une demande de nouveau mot de passe (demande faite par erreur, ou par quelqu'un
     * d'autre que la personne : l'administrateur l'a vérifié). Le mot de passe ne change pas.
     */
    public function dismissRequest(int $id): void
    {
        $request = PasswordRequest::findPending($id);
        if ($request === null) {
            Session::flash('info', t('pwreq.already_handled'));
            $this->redirect('/admin/users');
        }
        if (!AccountPolicy::seesResetRequest(Auth::user(), $request)) {
            $this->deny($request, 'password request dismiss');
        }

        PasswordRequest::dismiss($id, (int) Auth::id());
        Log::write('password_request_dismissed', 'user:' . $request['id']);

        Session::flash('success', t('pwreq.dismissed', ['name' => person($request)]));
        $this->redirect('/admin/users');
    }

    private function renderUsers(array $old, array $errors): void
    {
        $actor = Auth::user();
        $this->render('admin/users', [
            'title'      => t('nav.users'),
            'users'      => User::all(),
            // Seuls les rôles que la personne connectée peut attribuer sont proposés
            'roles'      => array_values(array_filter(
                User::roles(),
                static fn (array $role): bool => AccountPolicy::canAssign($actor, $role['code'])
            )),
            'allRoles'   => array_column(User::roles(), null, 'code'),
            'jobTitles'  => JobTitle::active(),
            'requests'   => PasswordRequest::pendingFor($actor),
            'actor'      => $actor,
            'old'        => $old,
            'errors'     => $errors,
        ]);
    }
}
