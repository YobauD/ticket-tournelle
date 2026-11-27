<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Session;
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
            Auth::LOCKED  => t('auth.locked', ['minutes' => Config::get('security.lockout_minutes')]),
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
    //  Mot de passe oublié : demande transmise aux administrateurs
    // ------------------------------------------------------------------

    public function showForgot(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->renderForgot([], []);
    }

    /**
     * Enregistre la demande et prévient les administrateurs qui peuvent la traiter.
     *
     * La réponse est la même que l'adresse corresponde à un compte ou non : la page ne doit
     * pas permettre de vérifier qui a un compte. Aucun mot de passe n'est envoyé par e-mail.
     * Limites : une demande en attente par compte (les administrateurs ne sont prévenus
     * qu'une fois) et un nombre de demandes par heure depuis un même poste.
     */
    public function forgot(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        // Identifiant de connexion, ou adresse e-mail pour qui ne se souvient plus de son identifiant
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
            $this->renderForgot($old, $errors);
            return;
        }

        $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
        $limit = (int) Config::get('security.reset_requests_per_hour', 5);
        if (PasswordRequest::recentFromIp($ip, 60) >= $limit) {
            // Pas d'inscription au journal ici : sinon un robot pourrait le remplir
            http_response_code(429);
            $this->renderForgot($old, ['form' => t('forgot.throttled')]);
            return;
        }

        $user = User::findByUsernameOrEmail($identifier);
        if ($user === null) {
            Log::write('password_request_unknown', 'login:' . mb_substr($identifier, 0, 80));
        } elseif ((int) $user['is_active'] !== 1) {
            Log::write('password_request', 'user:' . $user['id'], 'compte désactivé : non transmise');
        } elseif (PasswordRequest::hasPending((int) $user['id'])) {
            Log::write('password_request', 'user:' . $user['id'], 'déjà en attente : administrateurs non relancés');
        } else {
            PasswordRequest::create((int) $user['id'], $message !== '' ? $message : null);
            $notified = Notifier::passwordRequest($user, $message !== '' ? $message : null);
            Log::write('password_request', 'user:' . $user['id'], "administrateurs prévenus : $notified");
        }

        Session::flash('info', t('forgot.sent'));
        $this->redirect('/login');
    }

    private function renderForgot(array $old, array $errors): void
    {
        $this->render('auth/forgot', [
            'title'  => t('forgot.title'),
            'old'    => $old,
            'errors' => $errors,
        ], 'auth');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('info', t('auth.logged_out'));
        $this->redirect('/login');
    }
}
