<?php
/**
 * Onglets communs aux écrans d'administration.
 * Variable : $current (chemin de l'onglet affiché, ex. '/admin/categories').
 * Pastilles : demandes « mot de passe oublié » à traiter (Comptes) et lieux saisis par les
 * déclarants à vérifier (Lieux).
 */
use App\Core\Auth;
use App\Models\Location;
use App\Models\PasswordRequest;

// [nombre, texte de l'infobulle et des lecteurs d'écran]
$counts = [
    '/admin/users'     => [PasswordRequest::countFor(Auth::user()), 'pwreq.badge'],
    '/admin/locations' => [Location::countUnverified(), 'ref.unverified'],
];
$tabs = [
    '/admin/users'      => 'nav.users',
    '/admin/titles'     => 'nav.titles',
    '/admin/categories' => 'nav.categories',
    '/admin/locations'  => 'nav.locations',
    '/admin/logs'       => 'nav.logs',
];
?>
<nav class="tabs" aria-label="<?= e(t('nav.admin')) ?>">
    <?php foreach ($tabs as $href => $key): ?>
        <a href="<?= e(url($href)) ?>" <?= $current === $href ? 'aria-current="page"' : '' ?>>
            <?= e(t($key)) ?>
            <?php if (($counts[$href][0] ?? 0) > 0): ?>
                <span class="count count-alert" title="<?= e(t($counts[$href][1])) ?>">
                    <?= (int) $counts[$href][0] ?><span class="sr-only"> <?= e(t($counts[$href][1])) ?></span>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
