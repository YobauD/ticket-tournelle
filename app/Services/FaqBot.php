<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Lang;

/**
 * Assistant d'aide : répond aux questions à partir du contenu de la FAQ (app/Lang/help_xx.php).
 *
 * Tout se passe sur le serveur du lycée, sans service extérieur ni intelligence artificielle :
 * aucune question ne quitte l'établissement (cahier des charges, § 2.3.1 et contrainte C3).
 *
 * Fonctionnement, en quatre étapes :
 *  1. normalisation : minuscules, accents retirés, expressions et synonymes ramenés à un mot
 *     unique (« projo », « vidéoprojecteur » et « projector » deviennent « videoprojecteur ») ;
 *  2. indexation : chaque question de la FAQ reçoit un poids par mot, selon l'endroit où il
 *     apparaît (titre de la question > mots-clés > titre de la section > texte de la réponse) ;
 *  3. score : pour chaque question, somme des poids des mots de la demande, multipliés par leur
 *     rareté (un mot présent partout, comme « ordinateur », compte moins qu'« imprimante »),
 *     puis ajusté selon la part des mots de la demande trouvés (couverture) ;
 *  4. décision : une réponse nette est affichée ; si deux réponses sont proches, l'assistant
 *     propose de choisir ; si rien ne correspond assez, il le dit et propose de déclarer.
 *
 * Les réponses sont des structures de données (JSON) : le texte reste du texte, affiché par
 * le navigateur sans jamais être interprété comme du HTML.
 */
final class FaqBot
{
    /** Poids d'un mot selon l'endroit où il apparaît dans la FAQ. */
    private const WEIGHT_QUESTION = 3.0;
    private const WEIGHT_KEYWORD  = 2.5;
    private const WEIGHT_SECTION  = 1.0;
    private const WEIGHT_ANSWER   = 0.6;

    /** Score minimal pour proposer une réponse, et écart en dessous duquel on demande de choisir. */
    private const MIN_SCORE       = 4.0;
    private const AMBIGUITY_RATIO = 0.8;
    private const MAX_CHOICES     = 4;

    /** Part du score conservée quand une seule partie des mots de la question correspond. */
    private const COVERAGE_BASE   = 0.4;

    /** Longueur maximale d'une question, et nombre maximal de mots analysés. */
    public const MAX_LENGTH = 300;
    private const MAX_WORDS = 20;

    /** Expressions de plusieurs mots ramenées à un seul, avant le découpage en mots. */
    private const PHRASES = [
        'mots de passe' => 'motdepasse', 'mot de passe' => 'motdepasse', 'pass word' => 'motdepasse',
        'wi fi' => 'wifi', 'haut parleurs' => 'audio', 'haut parleur' => 'audio', 'pas de son' => 'audio',
        'le son' => 'audio', 'du son' => 'audio',
        'tableau interactif' => 'tableau', 'tableaux interactifs' => 'tableau',
        'pas de signal' => 'signal', 'no signal' => 'signal', 'unite centrale' => 'ordinateur',
        'mise a jour' => 'miseajour', 'mises a jour' => 'miseajour',
        'prise electrique' => 'electrique', 'power socket' => 'electrique', 'power strip' => 'multiprise',
        'paper jam' => 'bourrage', 'sans fil' => 'sansfil', 'log in' => 'connecter', 'sign in' => 'connecter',
        'quelqu un' => 'humain', 'une personne' => 'humain', 'un humain' => 'humain',
    ];

    /** Synonymes : variantes françaises et anglaises ramenées à une forme unique. */
    private const SYNONYMS = [
        'ordi' => 'ordinateur', 'pc' => 'ordinateur', 'poste' => 'ordinateur', 'computer' => 'ordinateur',
        'laptop' => 'ordinateur', 'tower' => 'ordinateur',
        'projo' => 'videoprojecteur', 'projecteur' => 'videoprojecteur', 'projector' => 'videoprojecteur',
        'beamer' => 'videoprojecteur', 'videoproj' => 'videoprojecteur', 'retroprojecteur' => 'videoprojecteur',
        'mdp' => 'motdepasse', 'password' => 'motdepasse', 'pwd' => 'motdepasse',
        'wireless' => 'sansfil', 'wlan' => 'wifi',
        'imprimer' => 'imprimante', 'impression' => 'imprimante', 'printer' => 'imprimante', 'print' => 'imprimante',
        'printing' => 'imprimante', 'photocopieuse' => 'imprimante', 'copieur' => 'imprimante', 'copier' => 'imprimante',
        'encre' => 'toner', 'cartouche' => 'toner', 'ink' => 'toner', 'cartridge' => 'toner',
        'son' => 'audio', 'sound' => 'audio', 'enceinte' => 'audio', 'enceintes' => 'audio', 'speaker' => 'audio',
        'speakers' => 'audio',
        'tbi' => 'tableau', 'vpi' => 'tableau', 'tni' => 'tableau', 'whiteboard' => 'tableau', 'smartboard' => 'tableau',
        'stylo' => 'stylet', 'pen' => 'stylet',
        'moniteur' => 'ecran', 'monitor' => 'ecran', 'screen' => 'ecran', 'display' => 'ecran',
        'neon' => 'eclairage', 'lumiere' => 'eclairage', 'light' => 'eclairage', 'lighting' => 'eclairage',
        'ampoule' => 'eclairage', 'bulb' => 'eclairage', 'plafonnier' => 'eclairage',
        'appli' => 'logiciel', 'application' => 'logiciel', 'programme' => 'logiciel', 'software' => 'logiciel',
        'program' => 'logiciel', 'app' => 'logiciel',
        'verrouille' => 'bloque', 'locked' => 'bloque', 'blocked' => 'bloque',
        'freeze' => 'fige', 'frozen' => 'fige', 'stuck' => 'fige',
        'rame' => 'lent', 'lenteur' => 'lent', 'slow' => 'lent',
        'crash' => 'plante', 'crashe' => 'plante',
        'oubli' => 'oublie', 'forgot' => 'oublie', 'forgotten' => 'oublie',
        'lost' => 'perdu', 'deleted' => 'supprime', 'efface' => 'supprime',
        'electricite' => 'electrique', 'electric' => 'electrique', 'electricity' => 'electrique',
        'courant' => 'electrique', 'socket' => 'prise', 'outlet' => 'prise',
        'radiateur' => 'chauffage', 'radiator' => 'chauffage', 'heating' => 'chauffage',
        'chaise' => 'mobilier', 'chair' => 'mobilier', 'meuble' => 'mobilier', 'furniture' => 'mobilier',
        'table' => 'mobilier', 'desk' => 'mobilier',
        'anglais' => 'langue', 'anglai' => 'langue', 'english' => 'langue', 'francais' => 'langue',
        'francai' => 'langue', 'french' => 'langue',
        'language' => 'langue', 'traduction' => 'langue', 'translation' => 'langue',
        'declarer' => 'declarer', 'signaler' => 'declarer', 'report' => 'declarer', 'declare' => 'declarer',
        'panne' => 'panne', 'incident' => 'panne', 'probleme' => 'panne', 'souci' => 'panne', 'problem' => 'panne', 'issue' => 'panne',
        'fumee' => 'fumee', 'smoke' => 'fumee', 'feu' => 'feu', 'fire' => 'feu', 'flamme' => 'feu', 'flame' => 'feu',
        'incendie' => 'feu', 'brule' => 'brule', 'burning' => 'brule', 'burnt' => 'brule',
        'etincelle' => 'etincelle', 'spark' => 'etincelle', 'electrocution' => 'electrocution',
        'shock' => 'electrocution', 'inondation' => 'eau', 'flood' => 'eau', 'water' => 'eau', 'leak' => 'fuite',
        'technicien' => 'humain', 'technician' => 'humain', 'referent' => 'humain', 'human' => 'humain',
        'agent' => 'humain',
    ];

    /**
     * Mots trop courants pour aider à choisir une réponse. Ils sont vérifiés avant et après
     * la simplification du pluriel (« dans » ne doit pas devenir « dan » et passer le filtre).
     */
    private const STOPWORDS = [
        // français
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'au', 'aux', 'et', 'ou', 'en', 'dans', 'sur',
        'pour', 'par', 'avec', 'sans', 'ne', 'pas', 'plus', 'que', 'qui', 'quoi', 'comment', 'pourquoi',
        'je', 'me', 'mon', 'ma', 'mes', 'tu', 'te', 'ton', 'ta', 'tes', 'vous', 'votre', 'vos', 'il', 'elle',
        'on', 'nous', 'nos', 'notre', 'ils', 'elles', 'ce', 'cet', 'cette', 'ces', 'est', 'sont', 'suis',
        'ai', 'as', 'avons', 'avez', 'ont', 'fait', 'faire', 'peux', 'peut', 'puis', 'dois', 'doit',
        'quel', 'quelle', 'quels', 'quelles', 'ca', 'cela', 'ceci', 'qu', 'se', 'si', 'tout', 'tous',
        'tres', 'toujours', 'aide', 'aider', 'aidez', 'besoin', 'voudrais', 'veux', 'veut', 'faut',
        'svp', 'stp', 'marche', 'fonctionne', 'salle', 'cours', 'ya', 'jai', 'mais', 'donc', 'alors',
        'comme', 'leur', 'leurs', 'sa', 'ses', 'beaucoup', 'bcp', 'bien', 'vraiment', 'mettre', 'met',
        // anglais
        'the', 'an', 'of', 'to', 'in', 'on', 'at', 'for', 'with', 'without', 'is', 'are', 'am', 'be',
        'my', 'your', 'it', 'its', 'this', 'that', 'how', 'what', 'why', 'do', 'does', 'can', 'could',
        'we', 'you', 'not', 'no', 'dont', 'doesnt', 'wont', 'cant', 'there', 'please', 'help', 'need',
        'want', 'working', 'work', 'works', 'room', 'and', 'or', 'from', 'has', 'have', 'get', 'got',
        'was', 'will', 'should', 'when', 'where', 'which', 'some', 'any', 'all', 'much', 'lot', 'very', 'so',
    ];

    /** Intentions particulières, reconnues avant la recherche dans la FAQ. */
    private const DANGER_WORDS   = ['fumee', 'feu', 'brule', 'etincelle', 'electrocution', 'eau', 'fuite', 'danger', 'hazard'];
    private const GREETING_WORDS = ['bonjour', 'salut', 'bonsoir', 'coucou', 'hello', 'hi', 'hey', 'yo'];
    private const THANKS_WORDS   = ['merci', 'thank', 'thanks', 'thx', 'super', 'parfait', 'top', 'genial', 'great', 'perfect'];
    private const HUMAN_WORDS    = ['humain'];

    /** Questions de la FAQ indexées : liste de [section, question, poids par mot]. */
    private array $entries = [];

    /** Nombre de questions dans lesquelles chaque mot apparaît (pour calculer sa rareté). */
    private array $documentFrequency = [];

    /**
     * @param array $sections   contenu de app/Lang/help_xx.php
     * @param array $categories catégories actives, indexées par id
     * @param array $priorities priorités, indexées par code
     */
    public function __construct(
        private readonly array $sections,
        private readonly array $categories,
        private readonly array $priorities,
        private readonly bool $loggedIn,
        private readonly ?string $firstName = null
    ) {
        $this->buildIndex();
    }

    // ------------------------------------------------------------------
    //  Réponses aux différentes demandes du navigateur
    // ------------------------------------------------------------------

    /** Ouverture de la conversation : salutation et choix d'un sujet. */
    public function start(): array
    {
        $text = $this->firstName !== null
            ? Lang::get('bot.greeting_named', ['name' => $this->firstName])
            : Lang::get('bot.greeting');

        return $this->reply([$this->text($text)], $this->topicChips());
    }

    /** Liste des questions d'un sujet. */
    public function section(string $sectionId): array
    {
        foreach ($this->sections as $section) {
            if ($section['id'] === $sectionId) {
                $chips = array_map(
                    fn (array $item): array => $this->chip($item['q'], ['id' => $item['id']]),
                    $section['items']
                );
                return $this->reply(
                    [$this->text(Lang::get('bot.section_intro', ['topic' => $this->topicLabel($sectionId)]))],
                    $chips
                );
            }
        }
        return $this->start();
    }

    /** Réponse à une question précise de la FAQ, choisie dans une liste. */
    public function answerById(string $itemId): array
    {
        $entry = $this->findEntry($itemId);
        return $entry === null ? $this->start() : $this->answer($entry);
    }

    /** Réponse de l'utilisateur à « Est-ce que cela a réglé le problème ? ». */
    public function feedback(string $itemId, bool $solved): array
    {
        if ($solved) {
            return $this->reply([$this->text(Lang::get('bot.glad'))], [$this->otherQuestionChip()]);
        }

        $entry  = $this->findEntry($itemId);
        $ticket = $entry['item']['ticket'] ?? null;

        if ($ticket !== null) {
            $link = self::ticketLink($ticket, $this->categories, $this->priorities, $this->loggedIn);
            return $this->reply(
                [$this->text(Lang::get($this->loggedIn ? 'bot.declare_prefilled' : 'bot.declare_login'))],
                [$this->linkChip($link['label'], $link['href'], true), $this->otherQuestionChip()]
            );
        }

        return $this->reply([$this->text(Lang::get('bot.more_help'))], [
            $this->linkChip(Lang::get('bot.open_help'), url('/help') . ($entry ? '#' . $entry['item']['id'] : '')),
            $this->declareChip(),
            $this->otherQuestionChip(),
        ]);
    }

    /** Question libre tapée par l'utilisateur. */
    public function ask(string $question): array
    {
        $words = $this->words($question);

        // 1. Danger : la consigne de sécurité passe avant tout le reste
        if (array_intersect($words, self::DANGER_WORDS)) {
            return $this->answer($this->findEntry('danger'), true);
        }

        $ranked = $this->rank($words);
        $best   = $ranked[0] ?? null;

        // 2. Formules de politesse seules (« bonjour », « merci ») : pas de recherche dans la FAQ
        $meaningful = array_diff($words, self::GREETING_WORDS, self::THANKS_WORDS);
        if ($meaningful === [] && array_intersect($words, self::THANKS_WORDS)) {
            return $this->reply([$this->text(Lang::get('bot.thanks'))], [$this->otherQuestionChip()]);
        }
        if ($meaningful === [] && array_intersect($words, self::GREETING_WORDS)) {
            return $this->start();
        }

        // 3. Demande à parler à quelqu'un, sans question technique reconnue
        if (array_intersect($words, self::HUMAN_WORDS) && ($best === null || $best['score'] < self::MIN_SCORE * 1.5)) {
            return $this->reply([$this->text(Lang::get('bot.human'))], [$this->declareChip(), $this->topicsChip()]);
        }

        // 4. Rien d'assez proche dans la FAQ
        if ($best === null || $best['score'] < self::MIN_SCORE) {
            return $this->reply([$this->text(Lang::get('bot.none'))], [
                $this->topicsChip(),
                $this->declareChip(),
                $this->linkChip(Lang::get('bot.open_help'), url('/help')),
            ]);
        }

        // 5. Plusieurs réponses proches : on demande à l'utilisateur de choisir
        $close = array_values(array_filter(
            $ranked,
            static fn (array $r): bool => $r['score'] >= $best['score'] * self::AMBIGUITY_RATIO
        ));
        if (count($close) > 1) {
            $chips = array_map(
                fn (array $r): array => $this->chip($r['entry']['item']['q'], ['id' => $r['entry']['item']['id']]),
                array_slice($close, 0, self::MAX_CHOICES)
            );
            return $this->reply([$this->text(Lang::get('bot.choices'))], [...$chips, $this->otherQuestionChip()]);
        }

        // 6. Une réponse nette
        return $this->answer($best['entry']);
    }

    /**
     * Lien « Déclarer un incident » prérempli avec la catégorie et la priorité conseillées.
     * Partagé avec la page d'aide. Un visiteur non connecté est envoyé vers la connexion.
     *
     * @return array{href: string, label: string, detail: string}
     */
    public static function ticketLink(array $ticket, array $categories, array $priorities, bool $loggedIn): array
    {
        [$categoryId, $priorityCode] = $ticket;
        $params = ['priority' => $priorityCode];
        $detail = [];

        if (isset($categories[$categoryId])) {
            $params['category'] = $categoryId;
            $detail[] = label($categories[$categoryId], 'label');
        }
        if (isset($priorities[$priorityCode])) {
            $detail[] = Lang::get('help.priority', ['priority' => mb_strtolower(label($priorities[$priorityCode], 'label'))]);
        }
        $detailText = implode(', ', $detail);

        return [
            'href'   => $loggedIn ? url('/tickets/new?' . http_build_query($params)) : url('/login'),
            'label'  => $loggedIn
                ? Lang::get('ticket.new') . ($detailText !== '' ? ' (' . $detailText . ')' : '')
                : Lang::get('bot.login_to_declare'),
            'detail' => $detailText,
        ];
    }

    // ------------------------------------------------------------------
    //  Compréhension de la question
    // ------------------------------------------------------------------

    /** Mots d'une question tapée par l'utilisateur (longueur et nombre de mots limités). */
    public function words(string $question): array
    {
        return array_slice($this->tokenize(mb_substr($question, 0, self::MAX_LENGTH)), 0, self::MAX_WORDS);
    }

    /**
     * Découpe un texte en mots normalisés : minuscules, sans accents, synonymes ramenés
     * à une forme unique, pluriels simplifiés, mots vides retirés, doublons supprimés.
     *
     * @param bool $query true pour une question tapée ou des mots-clés, false pour le texte
     *                    rédigé de la FAQ. Dans ce texte, « son » est presque toujours un
     *                    possessif (« son interrupteur ») : seule l'expression « pas de son »
     *                    y désigne l'audio. Dans une question courte, « son » veut dire l'audio.
     */
    private function tokenize(string $text, bool $query = true): array
    {
        $text = mb_strtolower($text);
        $text = strtr($text, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u',
            'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae',
        ]);
        // Tout ce qui n'est ni lettre ni chiffre devient un espace : « l’écran » donne « l ecran »
        $text = ' ' . trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text)) . ' ';
        foreach (self::PHRASES as $phrase => $replacement) {
            $text = str_replace(' ' . $phrase . ' ', ' ' . $replacement . ' ', $text);
        }

        $words = [];
        foreach (explode(' ', trim($text)) as $word) {
            if (!$query && $word === 'son') {
                continue;
            }
            $word = self::SYNONYMS[$word] ?? $word;
            if (in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            // Pluriel simplifié : « ecrans » donne « ecran », « bureaux » donne « bureau »
            if (strlen($word) > 3 && preg_match('/[^s][sx]$/', $word)) {
                $word = substr($word, 0, -1);
            }
            $word = self::SYNONYMS[$word] ?? $word;
            if (strlen($word) >= 2 && !in_array($word, self::STOPWORDS, true)) {
                $words[$word] = $word;
            }
        }
        return array_values($words);
    }

    /** Questions de la FAQ classées par score décroissant (scores nuls exclus). */
    private function rank(array $words): array
    {
        // Mots connus de la FAQ : un numéro de salle (« A102 ») ou un mot absent partout
        // ne doit pas pénaliser la couverture des réponses
        $known = array_filter($words, function (string $word): bool {
            foreach (array_keys($this->documentFrequency) as $indexed) {
                if (self::similarity($word, (string) $indexed) > 0) {
                    return true;
                }
            }
            return false;
        });

        $ranked = [];
        foreach ($this->entries as $entry) {
            if ($entry['item']['id'] === 'danger') {
                continue; // traitée à part, avant le classement
            }
            $score   = 0.0;
            $matched = 0;
            foreach ($words as $word) {
                $best = 0.0;
                foreach ($entry['weights'] as $indexed => $weight) {
                    // PHP transforme une clé « 72 » en entier : on la remet en texte
                    $indexed    = (string) $indexed;
                    $similarity = self::similarity($word, $indexed);
                    if ($similarity > 0) {
                        $best = max($best, $similarity * $weight * $this->rarity($indexed));
                    }
                }
                $score += $best;
                $matched += $best > 0 ? 1 : 0;
            }
            // Couverture : « le vidéoprojecteur ne s'allume pas » doit préférer la réponse qui
            // parle des deux (vidéoprojecteur ET allumer) à celle qui ne parle que d'allumer
            $score *= self::COVERAGE_BASE + (1 - self::COVERAGE_BASE) * min(1, $matched / max(1, count($known)));
            if ($score > 0) {
                $ranked[] = ['entry' => $entry, 'score' => $score];
            }
        }
        usort($ranked, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        return $ranked;
    }

    /**
     * Ressemblance entre deux mots : 1 s'ils sont identiques ; 0,75 s'ils ont le même début
     * (« imprimer » et « imprimante », « allume » et « allumer ») ; 0 sinon.
     */
    private static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }
        $shortest = min(strlen($a), strlen($b));
        $prefix   = 0;
        while ($prefix < $shortest && $a[$prefix] === $b[$prefix]) {
            $prefix++;
        }
        return $prefix >= 4 && $prefix >= 0.75 * $shortest ? 0.75 : 0.0;
    }

    /** Rareté d'un mot dans la FAQ : un mot présent dans peu de questions est plus décisif. */
    private function rarity(string $word): float
    {
        return log(1 + count($this->entries) / max(1, $this->documentFrequency[$word] ?? 1));
    }

    /** Calcule, pour chaque question, le poids de chacun de ses mots. */
    private function buildIndex(): void
    {
        foreach ($this->sections as $section) {
            foreach ($section['items'] as $item) {
                $answerText = '';
                foreach ($item['a'] as [, $content]) {
                    $answerText .= ' ' . (is_array($content) ? implode(' ', $content) : $content);
                }

                $weights = [];
                // [texte, poids, s'agit-il de mots tapés par un utilisateur ?]
                $fields  = [
                    [$item['q'], self::WEIGHT_QUESTION, false],
                    [implode(' ', $item['keywords'] ?? []), self::WEIGHT_KEYWORD, true],
                    [$section['title'], self::WEIGHT_SECTION, false],
                    [$answerText, self::WEIGHT_ANSWER, false],
                ];
                foreach ($fields as [$text, $weight, $isQuery]) {
                    foreach ($this->tokenize($text, $isQuery) as $word) {
                        $weights[$word] = max($weights[$word] ?? 0.0, $weight);
                    }
                }
                foreach (array_keys($weights) as $word) {
                    $this->documentFrequency[$word] = ($this->documentFrequency[$word] ?? 0) + 1;
                }
                $this->entries[] = ['section' => $section, 'item' => $item, 'weights' => $weights];
            }
        }
    }

    private function findEntry(string $itemId): ?array
    {
        foreach ($this->entries as $entry) {
            if ($entry['item']['id'] === $itemId) {
                return $entry;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------
    //  Construction des réponses (données JSON envoyées au navigateur)
    // ------------------------------------------------------------------

    /** Réponse complète d'une question de la FAQ, suivie de « Est-ce réglé ? ». */
    private function answer(array $entry, bool $danger = false): array
    {
        $item     = $entry['item'];
        $isFix    = isset($item['ticket']);
        $messages = [];

        if ($danger) {
            $messages[] = $this->text(Lang::get('bot.danger_intro'));
        }
        $messages[] = [
            'kind'      => $danger ? 'danger' : 'answer',
            'id'        => $item['id'],
            'title'     => $item['q'],
            'blocks'    => $item['a'],
            'helpUrl'   => url('/help') . '#' . $item['id'],
            'helpLabel' => Lang::get('bot.see_in_help'),
        ];

        if ($danger) {
            return $this->reply($messages, [$this->topicsChip()]);
        }

        $messages[] = $this->text(Lang::get($isFix ? 'bot.ask_fixed' : 'bot.ask_answered'));
        return $this->reply($messages, [
            $this->chip(Lang::get($isFix ? 'bot.yes_fixed' : 'bot.yes_answered'), ['feedback' => 'yes', 'id' => $item['id']]),
            $this->chip(Lang::get($isFix ? 'bot.no_fixed' : 'bot.no_answered'), ['feedback' => 'no', 'id' => $item['id']]),
            $this->otherQuestionChip(),
        ]);
    }

    private function reply(array $messages, array $chips = []): array
    {
        return ['messages' => $messages, 'chips' => $chips];
    }

    private function text(string $text): array
    {
        return ['kind' => 'text', 'text' => $text];
    }

    /** Bouton de réponse rapide : son libellé s'affiche comme un message de l'utilisateur. */
    private function chip(string $label, array $payload): array
    {
        return ['label' => $label, 'payload' => $payload];
    }

    /** Lien présenté comme un bouton de réponse rapide. */
    private function linkChip(string $label, string $href, bool $primary = false): array
    {
        return ['label' => $label, 'href' => $href, 'primary' => $primary];
    }

    private function topicChips(): array
    {
        return array_map(
            fn (array $section): array => $this->chip($this->topicLabel($section['id']), ['section' => $section['id']]),
            $this->sections
        );
    }

    /** Libellé court d'un sujet (« Ordinateur »), à défaut le titre complet de la section. */
    private function topicLabel(string $sectionId): string
    {
        $key   = 'bot.topic.' . $sectionId;
        $label = Lang::get($key);
        if ($label !== $key) {
            return $label;
        }
        foreach ($this->sections as $section) {
            if ($section['id'] === $sectionId) {
                return $section['title'];
            }
        }
        return $sectionId;
    }

    private function topicsChip(): array
    {
        return $this->chip(Lang::get('bot.topics'), ['start' => '1']);
    }

    private function otherQuestionChip(): array
    {
        return $this->chip(Lang::get('bot.other'), ['start' => '1']);
    }

    private function declareChip(): array
    {
        return $this->loggedIn
            ? $this->linkChip(Lang::get('ticket.new'), url('/tickets/new'))
            : $this->linkChip(Lang::get('bot.login_to_declare'), url('/login'));
    }
}
