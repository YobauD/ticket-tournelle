<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Session;
use App\Models\EmailToken;
use App\Models\Log;
use App\Models\PasswordRequest;
use App\Models\User;
use App\Services\Notifier;

final class AuthController extends Controller
{
    /** Page d'accueil : chacun arrive sur l'écran utile à son rôle. */
    public function home(): void
    {
        $this->redirect(Auth::isStaff() ? '/queue' : '/tickets');
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->render('auth/login', ['title' => t('auth.title'), 'username' => '', 'error' => null], 'auth');
    }

    public function login(): void
    {
        $username = mb_strtolower($this->input('username'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : ''; // jamais « nettoyé »

        $error = match (Auth::attempt($username, $password)) {
            Auth::OK      => null,
            Auth::LOCKED    => t('auth.locked', ['minutes' => Config::get('security.lockout_minutes')]),
            Auth::THROTTLED => t('auth.throttled', ['minutes' => Config::get('security.lockout_minutes')]),
            default       => t('auth.invalid'),
        };

        if ($error === null) {
            // La personne a retrouvé son mot de passe : sa demande éventuelle n'a plus d'objet,
            // et un administrateur ne doit pas lui imposer un mot de passe provisoire inutile
            PasswordRequest::closeFor((int) Auth::id(), 'cancelled', null);
            $this->redirect('/');
        }

        http_response_code(401);
        $this->render('auth/login', ['title' => t('auth.title'), 'username' => $username, 'error' => $error], 'auth');
    }

    // ------------------------------------------------------------------
    //  Mot de passe oublié
    //
    //  1. /password/forgot : la personne saisit son identifiant ; un lien « nouveau mot de
    //     passe » part vers son adresse e-mail confirmée. Sans adresse, un administrateur
    //     est prévenu à la place.
    //  2. /password/reset  : le lien ouvre le formulaire du nouveau mot de passe.
    //  3. /password/admin  : en secours (plus d'accès à sa messagerie), demande à un
    //     administrateur, qui remet un mot de passe provisoire après vérification d'identité.
    //
    //  Les réponses sont les mêmes que le compte existe ou non : ces pages ne permettent pas
    //  de vérifier qui a un compte. Envois limités par poste et par compte.
    // ------------------------------------------------------------------

    public function showForgot(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->renderPage('auth/forgot', 'forgot.title', [], []);
    }

    public function forgot(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $identifier = mb_strtolower($this->input('identifier'));
        $old        = ['identifier' => $identifier];

        if ($identifier === '' || mb_strlen($identifier) > 190) {
            http_response_code(422);
            $this->renderPage('auth/forgot', 'forgot.title', $old, ['identifier' => t('forgot.error.identifier')]);
            return;
        }
        if ($this->throttled()) {
            http_response_code(429);
            $this->renderPage('auth/forgot', 'forgot.title', $old, ['form' => t('forgot.throttled')]);
            return;
        }

        $user = User::findByUsernameOrEmail($identifier);
        if ($user === null) {
            Log::write('password_request_unknown', 'login:' . mb_substr($identifier, 0, 80));
        } elseif ((int) $user['is_active'] !== 1) {
            Log::write('password_link_refused', 'user:' . $user['id'], 'compte désactivé');
        } elseif ($user['email'] === null) {
            // Aucune adresse confirmée : impossible d'envoyer un lien, un administrateur s'en charge
            $this->notifyAdmins($user, null, 'pas d\'adresse e-mail confirmée');
        } elseif (EmailToken::countRecent((int) $user['id'], EmailToken::RESET, 60) >= (int) Config::get('security.reset_links_per_hour', 3)) {
            Log::write('password_link_refused', 'user:' . $user['id'], 'trop de liens envoyés cette heure-ci');
        } else {
            $minutes = (int) Config::get('security.reset_link_minutes', 60);
            $token   = EmailToken::create((int) $user['id'], EmailToken::RESET, $user['email'], $minutes);
            Notifier::account($user, $user['email'], 'reset_link', full_url('/password/reset?token=' . $token), ['minutes' => $minutes]);
            Log::write('password_link_sent', 'user:' . $user['id'], $user['email']);
        }

        Session::flash('info', t('forgot.link_sent', ['minutes' => (int) Config::get('security.reset_link_minutes', 60)]));
        $this->redirect('/login');
    }

    /** Formulaire du nouveau mot de passe, ouvert depuis le lien reçu par e-mail. */
    public function showReset(): void
    {
        $token = $this->query('token');
        $valid = EmailToken::findValid($token, EmailToken::RESET) !== null;
        $this->renderPage('auth/reset', 'reset.title', ['token' => $valid ? $token : ''], $valid ? [] : ['form' => t('reset.invalid')]);
    }

    public function reset(): void
    {
        $token   = $this->input('token');
        $user    = EmailToken::findValid($token, EmailToken::RESET);
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($user === null) {
            http_response_code(410);
            $this->renderPage('auth/reset', 'reset.title', ['token' => ''], ['form' => t('reset.invalid')]);
            return;
        }

        $errors = [];
        if ($policy = Auth::passwordError($new, $user['username'], $user['email'] ?? '')) {
            $errors['new_password'] = t($policy, ['min' => Config::get('security.password_min_length')]);
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = t('password.mismatch');
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->renderPage('auth/reset', 'reset.title', ['token' => $token], $errors);
            return;
        }
        // Le lien est marqué utilisé avant le changement : deux envois simultanés ne passent pas
        if (!EmailToken::consume($user['token_id'])) {
            http_response_code(410);
            $this->renderPage('auth/reset', 'reset.title', ['token' => ''], ['form' => t('reset.invalid')]);
            return;
        }

        $id = (int) $user['id'];
        User::setPassword($id, Auth::hash($new), false);   // lève aussi un éventuel blocage
        EmailToken::invalidate($id, EmailToken::RESET);
        PasswordRequest::closeFor($id, 'done', null);       // une demande à l'administrateur n'a plus d'objet
        Log::write('password_reset_by_link', 'user:' . $id);
        if ($user['email'] !== null) {
            Notifier::account($user, $user['email'], 'password_changed');
        }

        Session::flash('success', t('reset.done'));
        $this->redirect('/login');
    }

    /** En secours : demande transmise aux administrateurs (plus d'accès à sa messagerie). */
    public function showAdminRequest(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->renderPage('auth/admin_request', 'pwadmin.title', [], []);
    }

    /**
     * Enregistre la demande et prévient les administrateurs qui peuvent la traiter.
     * Une demande en attente par compte : les administrateurs ne sont prévenus qu'une fois.
     */
    public function adminRequest(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $identifier = mb_strtolower($this->input('identifier'));
        $message    = $this->input('message');
        $old        = ['identifier' => $identifier, 'message' => $message];
        $errors     = [];

        if ($identifier === '' || mb_strlen($identifier) > 190) {
            $errors['identifier'] = t('forgot.error.identifier');
        }
        if (mb_strlen($message) > PasswordRequest::MESSAGE_MAX) {
            $errors['message'] = t('forgot.error.message', ['max' => PasswordRequest::MESSAGE_MAX]);
        }
        if ($errors !== []) {
            http_response_code(422);
            $this->renderPage('auth/admin_request', 'pwadmin.title', $old, $errors);
            return;
        }
        if ($this->throttled()) {
            http_response_code(429);
            $this->renderPage('auth/admin_request', 'pwadmin.title', $old, ['form' => t('forgot.throttled')]);
            return;
        }

        $user = User::findByUsernameOrEmail($identifier);
        if ($user === null) {
            Log::write('password_request_unknown', 'login:' . mb_substr($identifier, 0, 80));
        } elseif ((int) $user['is_active'] !== 1) {
            Log::write('password_request', 'user:' . $user['id'], 'compte désactivé : non transmise');
        } else {
            $this->notifyAdmins($user, $message !== '' ? $message : null, null);
        }

        Session::flash('info', t('forgot.sent'));
        $this->redirect('/login');
    }

    /** Crée la demande à l'administrateur (sauf si une est déjà en attente) et les prévient. */
    private function notifyAdmins(array $user, ?string $message, ?string $reason): void
    {
        $id = (int) $user['id'];
        if (PasswordRequest::hasPending($id)) {
            Log::write('password_request', 'user:' . $id, 'déjà en attente : administrateurs non relancés');
            return;
        }
        PasswordRequest::create($id, $message);
        $notified = Notifier::passwordRequest($user, $message);
        Log::write('password_request', 'user:' . $id, ($reason !== null ? $reason . ' ; ' : '') . "administrateurs prévenus : $notified");
    }

    /** Trop de demandes depuis ce poste cette heure-ci ? (pas d'inscription au journal, qu'un robot remplirait) */
    private function throttled(): bool
    {
        return PasswordRequest::recentFromIp($_SERVER['REMOTE_ADDR'] ?? '', 60)
            >= (int) Config::get('security.reset_requests_per_hour', 5);
    }

    private function renderPage(string $view, string $titleKey, array $old, array $errors): void
    {
        $this->render($view, ['title' => t($titleKey), 'old' => $old, 'errors' => $errors], 'auth');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('info', t('auth.logged_out'));
        $this->redirect('/login');
    }
}
