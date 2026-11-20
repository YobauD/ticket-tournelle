<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Lang;
use App\Models\Category;
use App\Models\Priority;
use App\Services\FaqBot;

/**
 * Aide : page FAQ et assistant conversationnel, construits sur le même contenu
 * (app/Lang/help_fr.php et help_en.php).
 *
 * Les deux sont accessibles sans être connecté, pour qu'une personne dont le compte
 * est bloqué puisse s'en servir.
 */
final class HelpController extends Controller
{
    /** Page d'aide complète. */
    public function index(): void
    {
        $this->render('help/index', [
            'title'      => t('help.title'),
            'sections'   => $this->sections(),
            'categories' => $this->categories(),
            'priorities' => $this->priorities(),
        ]);
    }

    /**
     * Assistant : reçoit un message du navigateur et renvoie la réponse en JSON.
     * Un seul des champs suivants est attendu :
     *   start=1        ouverture ou « Autre question »
     *   section=<id>   liste des questions d'un sujet
     *   id=<id>        réponse à une question choisie (avec feedback=yes|no : « Est-ce réglé ? »)
     *   q=<texte>      question libre
     * Comme tout formulaire POST, la demande porte le jeton CSRF, vérifié par le routeur.
     */
    public function ask(): void
    {
        $user = Auth::user();
        $bot  = new FaqBot(
            $this->sections(),
            $this->categories(),
            $this->priorities(),
            $user !== null,
            $user['first_name'] ?? null
        );

        $id       = $this->input('id');
        $feedback = $this->input('feedback');

        $response = match (true) {
            $this->input('start') !== ''              => $bot->start(),
            $this->input('section') !== ''            => $bot->section($this->input('section')),
            $id !== '' && in_array($feedback, ['yes', 'no'], true) => $bot->feedback($id, $feedback === 'yes'),
            $id !== ''                                => $bot->answerById($id),
            $this->input('q') !== ''                  => $bot->ask(mb_substr($this->input('q'), 0, FaqBot::MAX_LENGTH)),
            default                                   => $bot->start(),
        };

        $this->json($response);
    }

    private function sections(): array
    {
        return require BASE_PATH . '/app/Lang/help_' . Lang::locale() . '.php';
    }

    /** Catégories actives, par id : un lien vers une catégorie désactivée n'est pas proposé. */
    private function categories(): array
    {
        return array_column(Category::active(), null, 'id');
    }

    private function priorities(): array
    {
        return array_column(Priority::all(), null, 'code');
    }
}
