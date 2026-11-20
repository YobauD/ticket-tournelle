<?php
// Banc d'essai de l'assistant d'aide : chaque question réaliste est comparée à la réponse attendue.
// À relancer après toute modification de app/Lang/help_fr.php, help_en.php ou de app/Services/FaqBot.php.
// Usage : php scripts/test_assistant.php            (affiche seulement les écarts)
//         php scripts/test_assistant.php verbose    (affiche chaque question)
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Lang;
use App\Services\FaqBot;

$cases = [
    'fr' => [
        // Audiovisuel
        ['le projo marche pas', 'projecteur-sans-image'],
        ['vidéoprojecteur pas d\'image', 'projecteur-sans-image'],
        ['le videoprojecteur ne s\'allume pas', 'projecteur-sans-image'],
        ['voyant rouge qui clignote sur le projecteur', 'projecteur-sans-image'],
        ['l\'image est floue', 'image-floue'],
        ['image coupée au tableau', 'image-floue'],
        ['pas de son', 'pas-de-son'],
        ['le son ne marche plus en A102', 'pas-de-son'],
        ['les enceintes ne font aucun bruit', 'pas-de-son'],
        ['le stylet du TBI ne répond pas', 'tableau-interactif'],
        ['tableau interactif bloqué', 'tableau-interactif'],
        // Compte
        ['j\'ai oublié mon mot de passe', 'mot-de-passe-oublie'],
        ['mon compte est bloqué', 'mot-de-passe-oublie'],
        ['mdp oublié', 'mot-de-passe-oublie'],
        ['comment changer mon mdp', 'changer-mot-de-passe'],
        ['première connexion', 'premiere-connexion'],
        ['comment se connecter', 'connexion'],
        ['mettre le site en anglais', 'langue'],
        // Ordinateurs
        ['mon ordi ne s\'allume plus', 'pc-ne-demarre-pas'],
        ['le pc ne démarre pas', 'pc-ne-demarre-pas'],
        ['écran noir', 'ecran-noir'],
        ['l\'écran affiche pas de signal', 'ecran-noir'],
        ['la souris ne marche plus', 'clavier-souris'],
        ['clavier qui répond pas', 'clavier-souris'],
        ['mon ordinateur rame', 'lent-ou-fige'],
        ['l\'ordi est figé', 'lent-ou-fige'],
        ['ordinateur très lent', 'lent-ou-fige'],
        ['l\'imprimante n\'imprime pas', 'imprimante'],
        ['bourrage papier', 'imprimante'],
        ['plus d\'encre dans l\'imprimante', 'imprimante'],
        ['la photocopieuse est en panne', 'imprimante'],
        // Réseau
        ['le wifi ne marche pas', 'wifi'],
        ['impossible de me connecter au wi-fi', 'wifi'],
        ['pas internet sur mon pc', 'pas-internet'],
        ['plus de réseau dans la salle', 'pas-internet'],
        ['pronote ne s\'ouvre pas', 'ent-ou-site'],
        ['l\'ENT est inaccessible', 'ent-ou-site'],
        // Logiciels
        ['le logiciel plante', 'logiciel-plante'],
        ['l\'application se ferme toute seule', 'logiciel-plante'],
        ['licence expirée', 'licence'],
        ['j\'ai perdu mon fichier word', 'fichier-perdu'],
        ['document supprimé par erreur', 'fichier-perdu'],
        ['installer un logiciel', 'installer-logiciel'],
        // Bâtiment
        ['le néon clignote', 'eclairage'],
        ['la lumière ne s\'allume pas', 'eclairage'],
        ['la prise électrique ne marche pas', 'prise-electrique'],
        ['plus de courant', 'prise-electrique'],
        ['il fait froid le radiateur est éteint', 'chauffage-fenetre'],
        ['la fenêtre ne ferme plus', 'chauffage-fenetre'],
        ['une chaise cassée', 'mobilier'],
        // Tickets
        ['comment déclarer un incident', 'declarer'],
        ['je veux signaler une panne', 'declarer'],
        ['quelle priorité choisir', 'priorite'],
        ['c\'est urgent', 'priorite'],
        ['que veut dire en attente', ['en-attente', 'statuts']],
        ['mon ticket est résolu mais c\'est toujours en panne', 'resolu'],
        ['comment suivre mon ticket', 'suivre'],
        ['ajouter une photo à mon ticket', 'completer'],
        ['le problème revient', 'probleme-revient'],
        // Intentions
        ['ça sent le brûlé', 'DANGER'],
        ['il y a de la fumée qui sort du pc', 'DANGER'],
        ['des étincelles sur la prise', 'DANGER'],
        ['bonjour', 'START'],
        ['merci beaucoup', 'THANKS'],
        ['je veux parler à un technicien', 'HUMAN'],
        ['asdfgh', 'NONE'],
        ['recette des crêpes', 'NONE'],
    ],
    'en' => [
        ['the projector shows nothing', 'projecteur-sans-image'],
        ['forgot my password', 'mot-de-passe-oublie'],
        ['no internet', 'pas-internet'],
        ['printer paper jam', 'imprimante'],
        ['wifi not working', 'wifi'],
        ['no sound', 'pas-de-son'],
        ['computer is very slow', 'lent-ou-fige'],
        ['black screen', 'ecran-noir'],
        ['license expired', 'licence'],
        ['how do I report an incident', 'declarer'],
        ['smoke coming from the computer', 'DANGER'],
        ['hello', 'START'],
        ['thanks', 'THANKS'],
    ],
];

/** Résume la réponse de l'assistant : identifiant de la réponse, ou type d'intention. */
function outcome(array $r): array
{
    foreach ($r['messages'] as $m) {
        if ($m['kind'] === 'danger') {
            return ['DANGER'];
        }
        if ($m['kind'] === 'answer') {
            return [$m['id']];
        }
    }
    $text = $r['messages'][0]['text'] ?? '';
    $ids  = array_values(array_filter(array_map(fn ($c) => $c['payload']['id'] ?? null, $r['chips'])));
    if ($ids) {
        return ['CHOICES', ...$ids];
    }
    foreach (['bot.greeting' => 'START', 'bot.greeting_named' => 'START', 'bot.thanks' => 'THANKS', 'bot.human' => 'HUMAN', 'bot.none' => 'NONE'] as $key => $label) {
        if ($text === Lang::get($key)) {
            return [$label];
        }
    }
    return ['?'];
}

$verbose = ($argv[1] ?? '') === 'verbose';
$total = $ok = $choices = 0;
foreach ($cases as $locale => $list) {
    Lang::set($locale);
    $bot = new FaqBot(require BASE_PATH . "/app/Lang/help_$locale.php", [], [], true);
    foreach ($list as [$question, $expected]) {
        $total++;
        $got      = outcome($bot->ask($question));
        $expected = (array) $expected;
        $direct   = in_array($got[0], $expected, true);
        // Une liste de choix contenant la bonne réponse en première position est acceptable
        $inChoices = $got[0] === 'CHOICES' && in_array($got[1] ?? '', $expected, true);
        if ($direct) {
            $ok++;
        } elseif ($inChoices) {
            $choices++;
        }
        if ($verbose || (!$direct && !$inChoices)) {
            printf("%s %-4s %-52s attendu %-24s obtenu %s\n", $direct ? 'OK ' : ($inChoices ? '~  ' : 'KO '), $locale, $question, implode('|', $expected), implode(', ', $got));
        }
    }
}
printf("\n%d questions : %d réponses directes justes, %d bonnes réponses proposées en premier choix, %d erreurs\n",
    $total, $ok, $choices, $total - $ok - $choices);
