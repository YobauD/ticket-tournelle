<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Config;
use App\Core\Session;
use App\Models\Log;
use App\Models\User;

final class AccountController extends Controller
{
    public function show(): void
    {
        $this->render('auth/account', ['title' => t('account.title'), 'errors' => []]);
    }

    /** L'utilisateur change son propre mot de passe (obligatoire après un mot de passe provisoire). */
    public function updatePassword(): void
    {
        $user    = Auth::user();
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $errors  = [];

        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = t('account.wrong_current');
        }
        if ($policy = Auth::passwordError($new, $user['username'], $user['email'])) {
            $errors['new_password'] = t($policy, ['min' => Config::get('security.password_min_length')]);
        } elseif ($new === $current) {
            $errors['new_password'] = t('password.same');
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = t('password.mismatch');
        }

        if ($errors) {
            http_response_code(422);
            $this->render('auth/account', ['title' => t('account.title'), 'errors' => $errors]);
            return;
        }

        User::setPassword((int) $user['id'], Auth::hash($new), false);
        Session::regenerate();
        Log::write('password_changed', 'user:' . $user['id']);

        Session::flash('success', t('account.password_updated'));
        $this->redirect('/');
    }
}
