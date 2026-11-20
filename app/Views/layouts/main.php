<?php
/**
 * Mise en page principale : barre supérieure + contenu, pour les personnes connectées.
 * L'aide (« ? ») et l'assistant n'apparaissent que pour une personne connectée ; sans
 * session, la barre n'affiche que la langue et un bouton de connexion.
 * Variables : $title, $content (HTML de la vue, déjà échappé par celle-ci).
 */
use App\Core\Auth;
use App\Core\Config;
use App\Core\Lang;
use App\Core\View;
use App\Models\PasswordRequest;

$user = Auth::user();

// Demandes « mot de passe oublié » qu'un administrateur peut traiter : pastille sur « Administration »
$pendingRequests = Auth::isAdmin() ? PasswordRequest::countFor($user) : 0;
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Entrée de menu à surligner. Un technicien qui ouvre un ticket vient de la file d'attente.
$active = match (true) {
    str_starts_with($path, url('/admin'))                                    => '/admin/users',
    str_starts_with($path, url('/stats'))                                    => '/stats',
    str_starts_with($path, url('/help'))                                     => '/help',
    str_starts_with($path, url('/queue'))                                    => '/queue',
    Auth::isStaff() && preg_match('#/tickets/\d+#', $path) === 1            => '/queue',
    str_starts_with($path, url('/tickets'))                                  => '/tickets',
    default                                                                  => '',
};

// Entrées du menu : [chemin, clé de traduction, rôles qui la voient]
$menu = [
    ['/tickets',     'nav.my_tickets', ['user', 'technician', 'admin', 'superadmin']],
    ['/queue',       'nav.queue',      ['technician', 'admin', 'superadmin']],
    ['/stats',       'nav.stats',      ['admin', 'superadmin']],
    ['/admin/users', 'nav.admin',      ['admin', 'superadmin']],
];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::locale()) ?>"
      data-password-show="<?= e(t('password.show')) ?>" data-password-hide="<?= e(t('password.hide')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? '') ?> – <?= e(Config::get('app.name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/logo.svg')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
    <script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(url('/')) ?>">
            <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="28" height="28">
            <span><?= e(Config::get('app.name')) ?></span>
        </a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-controls="main-nav" aria-expanded="false">
            <?= e(t('nav.menu')) ?>
        </button>

        <div class="topbar-menu" id="main-nav">
            <nav class="nav" aria-label="<?= e(t('nav.main')) ?>">
                <?php foreach ($menu as [$href, $key, $roles]): ?>
                    <?php if (Auth::is(...$roles)): ?>
                        <a href="<?= e(url($href)) ?>" <?= $active === $href ? 'aria-current="page"' : '' ?>>
                            <?= e(t($key)) ?>
                            <?php if ($href === '/admin/users' && $pendingRequests > 0): ?>
                                <span class="count count-alert" title="<?= e(t('pwreq.badge')) ?>">
                                    <?= $pendingRequests ?><span class="sr-only"> <?= e(t('pwreq.badge')) ?></span>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="topbar-tools">
                <?php if ($user !== null): ?>
                    <a class="btn btn-primary" href="<?= e(url('/tickets/new')) ?>"><?= e(t('ticket.new')) ?></a>
                <?php endif; ?>
                <?php if ($user !== null): ?>
                <a class="help-link" href="<?= e(url('/help')) ?>" title="<?= e(t('nav.help')) ?>" <?= $active === '/help' ? 'aria-current="page"' : '' ?>>
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12" y2="17"/></svg>
                    <span class="help-link-text"><?= e(t('nav.help')) ?></span>
                </a>
                <?php endif; ?>
                <?= View::render('partials/lang') ?>
                <?php if ($user === null): ?>
                    <a class="btn btn-primary" href="<?= e(url('/login')) ?>"><?= e(t('auth.submit')) ?></a>
                <?php else: ?>
                <a class="user-chip" href="<?= e(url('/account')) ?>" title="<?= e(t('account.title')) ?>">
                    <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1))) ?></span>
                    <span class="user-chip-text">
                        <strong><?= e(person($user)) ?></strong>
                        <small><?= e(user_subtitle($user)) ?></small>
                    </span>
                </a>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-quiet" type="submit"><?= e(t('auth.logout')) ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main class="page" id="main">
    <?= View::render('partials/flash') ?>
    <?= $content ?>
</main>
<?php if ($user !== null): ?>
    <?= View::render('partials/chatbot') ?>
<?php endif; ?>
</body>
</html>
