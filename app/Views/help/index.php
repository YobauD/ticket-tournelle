<?php
/**
 * Aide et questions fréquentes.
 * Variables : $sections (contenu de app/Lang/help_xx.php), $categories (actives, par id),
 *             $priorities (par code).
 *
 * Chaque question est un <details> : elle s'ouvre et se ferme sans JavaScript.
 * Avec JavaScript, le champ de recherche filtre les questions (assets/js/app.js).
 */
use App\Core\Auth;
use App\Services\FaqBot;

/** Texte échappé, où [[Ctrl]] devient une touche de clavier <kbd>. */
$text = static fn (string $value): string => preg_replace('/\[\[(.+?)\]\]/', '<kbd>$1</kbd>', e($value));

/** Lien « Le problème persiste ? » vers le formulaire prérempli (même règle que l'assistant). */
$persistLink = static function (array $ticket) use ($categories, $priorities): string {
    $link = FaqBot::ticketLink($ticket, $categories, $priorities, Auth::check());

    return '<p class="faq-persist"><span>' . e(t('help.persist')) . '</span> '
         . '<a href="' . e($link['href']) . '">' . e(Auth::check() ? t('ticket.new') : t('bot.login_to_declare')) . '</a>'
         . ($link['detail'] !== '' ? ' <span class="muted">(' . e($link['detail']) . ')</span>' : '')
         . '</p>';
};
?>
<div class="page-head">
    <div>
        <h1><?= e(t('help.title')) ?></h1>
        <p class="head-meta"><?= e(t('help.intro')) ?></p>
    </div>
</div>

<p class="help-ask" data-chatbot-open-wrap hidden>
    <button class="btn btn-primary" type="button" data-chatbot-open><?= e(t('bot.ask_assistant')) ?></button>
</p>

<div class="help-danger" role="note">
    <strong><?= e(t('help.danger_title')) ?></strong>
    <span><?= e(t('help.danger_text')) ?></span>
</div>

<div class="help-tools">
    <div class="field help-search" data-faq-search-field hidden>
        <label for="faq-search"><?= e(t('help.search')) ?></label>
        <input type="search" id="faq-search" placeholder="<?= e(t('help.search_placeholder')) ?>"
               autocomplete="off" data-faq-search aria-describedby="faq-results">
        <p class="hint" id="faq-results" aria-live="polite" data-faq-results
           data-one="<?= e(t('help.results_one')) ?>" data-many="<?= e(t('help.results_many')) ?>"></p>
    </div>

    <nav class="help-toc" aria-label="<?= e(t('help.contents')) ?>">
        <?php foreach ($sections as $section): ?>
            <a href="#<?= e($section['id']) ?>"><?= e($section['title']) ?></a>
        <?php endforeach; ?>
    </nav>
</div>

<div class="faq" data-faq>
    <?php foreach ($sections as $section): ?>
        <section class="faq-section" id="<?= e($section['id']) ?>" data-faq-section>
            <h2><?= e($section['title']) ?></h2>

            <?php foreach ($section['items'] as $item): ?>
                <details class="faq-item" id="<?= e($item['id']) ?>" data-faq-item>
                    <summary><?= $text($item['q']) ?></summary>
                    <div class="faq-answer">
                        <?php foreach ($item['a'] as [$type, $content]): ?>
                            <?php if ($type === 'steps' || $type === 'list'): ?>
                                <<?= $type === 'steps' ? 'ol' : 'ul' ?>>
                                    <?php foreach ($content as $line): ?>
                                        <li><?= $text($line) ?></li>
                                    <?php endforeach; ?>
                                </<?= $type === 'steps' ? 'ol' : 'ul' ?>>
                            <?php elseif ($type === 'note' || $type === 'warn'): ?>
                                <p class="callout callout-<?= e($type) ?>"><?= $text($content) ?></p>
                            <?php else: ?>
                                <p><?= $text($content) ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if (isset($item['ticket'])): ?>
                            <?= $persistLink($item['ticket']) ?>
                        <?php endif; ?>
                        <a class="faq-anchor" href="#<?= e($item['id']) ?>"><?= e(t('help.link')) ?></a>
                    </div>
                </details>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <div class="empty" data-faq-empty hidden>
        <p><?= e(t('help.no_result')) ?></p>
        <a class="btn btn-primary" href="<?= e(url(Auth::check() ? '/tickets/new' : '/login')) ?>"><?= e(t('ticket.new')) ?></a>
    </div>
</div>
