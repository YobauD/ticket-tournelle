<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Lang;
use App\Models\User;

final class LangController extends Controller
{
    /**
     * Change la langue de l'interface, puis revient sur la page d'origine.
     * Pour un utilisateur connecté, le choix est enregistré sur son compte :
     * il sera repris à la prochaine connexion et pour ses e-mails de notification.
     */
    public function switch(): void
    {
        $lang = $this->input('lang');

        if (Lang::isSupported($lang)) {
            Lang::set($lang);
            if (Auth::check()) {
                User::setLang(Auth::id(), $lang);
            }
        }

        // Retour limité à un chemin interne : pas de redirection vers un autre site
        $back = $this->input('back');
        if (!preg_match('#^/(?![/\\\\])[^\r\n]*$#', $back)) {
            $back = url('/');
        }
        header('Location: ' . $back);
        exit;
    }
}
