<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Session;
use App\Models\EmailToken;
use App\Models\Log;
use App\Models\User;
use App\Services\Notifier;

/**
 * Mon compte : mot de passe et adresse e-mail.
 *
 * L'adresse e-mail est choisie par la personne elle-même, à sa première connexion, et
 * confirmée par un lien : elle n'est enregistrée sur le compte qu'une fois le lien ouvert.
 * Elle sert aux notifications et au lien « mot de passe oublié ».
 */
final class AccountController extends Controller
{
    public function show(): void
    {
        $this->renderAccount([], []);
    }

    /**
     * Changement de mot de passe (obligatoire après un mot de passe provisoire).
     * À la première connexion, la personne indique aussi son adresse e-mail.
     */
    public function updatePassword(): void
    {
        $user      = Auth::user();
        $firstTime = (int) $user['must_change_password'] === 1;
        $current   = (string) ($_POST['current_password'] ?? '');
        $new       = (string) ($_POST['new_password'] ?? '');
        $confirm   = (string) ($_POST['confirm_password'] ?? '');
        $email     = mb_strtolower($this->input('email'));
        $errors    = [];

        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = t('account.wrong_current');
        }
        if ($policy = Auth::passwordError($new, $user['username'], $user['email'] ?? '', $email)) {
            $errors['new_password'] = t($policy, ['min' => Config::get('security.password_min_length')]);
        } elseif ($new === $current) {
            $errors['new_password'] = t('password.same');
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = t('password.mismatch');
        }
        if ($firstTime && ($emailError = $this->emailError($email, (int) $user['id']))) {
            $errors['email'] = $emailError;
        }

        if ($errors) {
            http_response_code(422);
            $this->renderAccount($errors, ['email' => $email]);
            return;
        }

        User::setPassword((int) $user['id'], Auth::hash($new), false);
        Session::regenerate();
        Log::write('password_changed', 'user:' . $user['id']);
        Session::flash('success', t('account.password_updated'));

        if ($firstTime && $email !== ($user['email'] ?? '')) {
            $this->sendConfirmation($user, $email);
        }
        $this->redirect('/');
    }

    /** Nouvelle adresse e-mail : le mot de passe actuel est demandé, puis un lien de confirmation part. */
    public function updateEmail(): void
    {
        $user    = Auth::user();
        $email   = mb_strtolower($this->input('email'));
        $errors  = [];

        if (!password_verify((string) ($_POST['email_password'] ?? ''), $user['password_hash'])) {
            $errors['email_password'] = t('account.wrong_current');
        }
        if ($emailError = $this->emailError($email, (int) $user['id'])) {
            $errors['email'] = $emailError;
        } elseif ($email === ($user['email'] ?? '')) {
            $errors['email'] = t('account.email_same');
        }
        if ($errors) {
            http_response_code(422);
            $this->renderAccount($errors, ['email' => $email]);
            return;
        }

        $this->sendConfirmation($user, $email);
        $this->redirect('/account#email');
    }

    /** Renvoie le lien de confirmation de l'adresse en attente (e-mail perdu ou expiré bientôt). */
    public function resendConfirmation(): void
    {
        $user    = Auth::user();
        $pending = EmailToken::pendingVerification((int) $user['id']);
        if ($pending === null) {
            Session::flash('info', t('account.no_pending'));
        } else {
            $this->sendConfirmation($user, $pending);
        }
        $this->redirect('/account#email');
    }

    /**
     * Lien de confirmation ouvert depuis l'e-mail. Accessible sans être connecté : on peut
     * l'ouvrir depuis un téléphone. Le jeton, secret et à usage unique, prouve que la personne
     * reçoit bien les e-mails à cette adresse.
     */
    public function confirmEmail(): void
    {
        $token = EmailToken::findValid($this->query('token'), EmailToken::VERIFY);
        $back  = Auth::check() ? '/account#email' : '/login';

        if ($token === null) {
            Session::flash('error', t('account.link_invalid'));
            $this->redirect($back);
        }
        if (User::emailTaken($token['token_email'], (int) $token['id'])) {
            EmailToken::consume($token['token_id']);
            Session::flash('error', t('account.error.email_taken'));
            $this->redirect($back);
        }
        if (!EmailToken::consume($token['token_id'])) {
            Session::flash('error', t('account.link_invalid'));
            $this->redirect($back);
        }

        $old = $token['email'];
        User::setEmail((int) $token['id'], $token['token_email']);
        Log::write('email_confirmed', 'user:' . $token['id'], ($old ?? '-') . ' -> ' . $token['token_email']);

        // L'ancienne adresse est prévenue : si ce n'est pas la personne qui a fait le changement,
        // elle le saura et pourra s'adresser à un administrateur
        if ($old !== null && $old !== $token['token_email']) {
            Notifier::account($token, $old, 'email_changed', null, ['email' => $token['token_email']]);
        }

        Session::flash('success', t('account.email_confirmed', ['email' => $token['token_email']]));
        $this->redirect($back);
    }

    // ------------------------------------------------------------------

    /** Envoie un lien de confirmation à $email (limité à 5 par heure pour un compte). */
    private function sendConfirmation(array $user, string $email): void
    {
        if (EmailToken::countRecent((int) $user['id'], EmailToken::VERIFY, 60) >= 5) {
            Session::flash('error', t('account.too_many_links'));
            return;
        }
        $hours = (int) Config::get('security.verify_link_hours', 48);
        $token = EmailToken::create((int) $user['id'], EmailToken::VERIFY, $email, $hours * 60);
        Notifier::account($user, $email, 'verify_link', full_url('/account/email/confirm?token=' . $token), [
            'email' => $email,
            'hours' => $hours,
        ]);
        Log::write('email_confirmation_sent', 'user:' . $user['id'], $email);
        Session::flash('info', t('account.confirmation_sent', ['email' => $email, 'hours' => $hours]));
    }

    /** Message d'erreur pour une adresse saisie, ou null si elle est acceptable. */
    private function emailError(string $email, int $userId): ?string
    {
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 190) {
            return t('account.error.email');
        }
        $domains = (array) Config::get('security.email_domains', []);
        if ($domains !== [] && !in_array(mb_substr(strrchr($email, '@'), 1), $domains, true)) {
            return t('account.error.email_domain', ['domains' => implode(', ', $domains)]);
        }
        if (User::emailTaken($email, $userId)) {
            return t('account.error.email_taken');
        }
        return null;
    }

    private function renderAccount(array $errors, array $old): void
    {
        $user = Auth::user();
        $this->render('auth/account', [
            'title'   => t('account.title'),
            'errors'  => $errors,
            'old'     => $old,
            'pending' => EmailToken::pendingVerification((int) $user['id']),
        ]);
    }
}
