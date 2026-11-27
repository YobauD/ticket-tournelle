<?php
/**
 * Fonctions utilitaires disponibles dans toutes les vues et tous les contrôleurs.
 */
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Lang;

/**
 * Échappe une valeur avant de l'afficher dans du HTML (protection XSS).
 * À utiliser sur TOUTE donnée affichée : <?= e($ticket['title']) ?>
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Traduit une clé dans la langue courante : t('ticket.new') */
function t(string $key, array $params = []): string
{
    return Lang::get($key, $params);
}

/**
 * Libellé d'un référentiel dans la langue courante.
 * label($ticket, 'status') lit $ticket['status_fr'] ou $ticket['status_en'].
 */
function label(array $row, string $field, ?string $locale = null): string
{
    $locale ??= Lang::locale();
    return (string) ($row[$field . '_' . $locale] ?? $row[$field . '_fr'] ?? '');
}

/** URL interne tenant compte du préfixe d'installation : url('/tickets') */
function url(string $path = '/'): string
{
    return rtrim((string) Config::get('app.base_url'), '/') . '/' . ltrim($path, '/');
}

/** URL absolue, pour les liens placés dans les e-mails. */
function full_url(string $path): string
{
    return rtrim((string) Config::get('app.full_url'), '/') . url($path);
}

/** Champ caché à placer dans chaque formulaire POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Date MySQL affichée au format de la langue courante. */
function dt(?string $datetime, bool $withTime = true, ?string $locale = null): string
{
    if ($datetime === null || $datetime === '') {
        return '—';
    }
    $locale ??= Lang::locale();
    $format = $locale === 'en'
        ? ($withTime ? 'j M Y, H:i' : 'j M Y')
        : ($withTime ? 'd/m/Y à H:i' : 'd/m/Y');
    return date($format, strtotime($datetime));
}

/** « Prénom NOM » à partir de colonnes préfixées (user_first_name, tech_last_name...). */
function person(array $row, string $prefix = ''): string
{
    $first = $row[$prefix . 'first_name'] ?? null;
    $last  = $row[$prefix . 'last_name'] ?? null;
    return $first === null ? '' : $first . ' ' . mb_strtoupper((string) $last);
}

/**
 * Ligne affichée sous le nom d'une personne : sa fonction (« Professeur »), ou à défaut son rôle ;
 * le niveau de support est ajouté pour un technicien (« Référent numérique · N2 »).
 */
function user_subtitle(array $user): string
{
    $text = $user['job_title_id'] !== null ? label($user, 'job_title') : label($user, 'role');
    if ($user['tech_level'] !== null) {
        $text .= ($user['job_title_id'] !== null ? ' · ' : ' ') . 'N' . (int) $user['tech_level'];
    }
    return $text;
}

/** Pastille de statut. La couleur vient de la classe CSS, le texte est traduit. */
function status_badge(array $row): string
{
    return '<span class="badge badge-status-' . e($row['status_code']) . '">' . e(label($row, 'status')) . '</span>';
}

/** Indicateur de priorité : un pictogramme de 1 à 4 barres + le libellé (lisible sans la couleur). */
function priority_badge(array $row): string
{
    return '<span class="priority priority-' . e($row['priority_code']) . '">'
         . '<span class="priority-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span>'
         . e(label($row, 'priority')) . '</span>';
}

/**
 * Durée lisible à partir d'un nombre de minutes : « 45 min », « 3 h 20 min », « 2 j 4 h ».
 * null (aucune donnée) donne un tiret.
 */
function duration(?float $minutes): string
{
    if ($minutes === null) {
        return '—';
    }
    $minutes = (int) round($minutes);
    if ($minutes < 60) {
        return $minutes . ' ' . t('unit.min');
    }
    if ($minutes < 24 * 60) {
        $rest = $minutes % 60;
        return intdiv($minutes, 60) . ' ' . t('unit.h') . ($rest > 0 ? ' ' . $rest . ' ' . t('unit.min') : '');
    }
    $hours = intdiv($minutes % (24 * 60), 60);
    return intdiv($minutes, 24 * 60) . ' ' . t('unit.d') . ($hours > 0 ? ' ' . $hours . ' ' . t('unit.h') : '');
}

/** Pourcentage arrondi (« 34 % » en français, « 34% » en anglais) ; tiret si le total est nul. */
function percent(int $part, int $total): string
{
    return $total === 0 ? '—' : t('unit.percent', ['value' => (int) round($part * 100 / $total)]);
}

/** Valeur numérique au format attendu par un attribut SVG (point décimal, quelle que soit la langue). */
function svg_number(float $value): string
{
    return number_format($value, 2, '.', '');
}

/**
 * Identifiant de la conversation avec l'assistant, propre à la session.
 * Une déconnexion détruit la session : la conversation suivante repart de zéro.
 */
function chat_key(): string
{
    $key = \App\Core\Session::get('chat_key');
    if (!is_string($key)) {
        $key = bin2hex(random_bytes(8));
        \App\Core\Session::set('chat_key', $key);
    }
    return $key;
}

/**
 * Texte en minuscules sans accents : « Hélène Gaëlle » -> « helene gaelle ».
 * Sert à comparer des lieux et à proposer un identifiant de connexion.
 */
function fold_accents(string $text): string
{
    return strtr(mb_strtolower($text), [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e',
        'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ó' => 'o',
        'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae', 'ß' => 'ss',
    ]);
}
