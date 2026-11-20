<?php
/**
 * Assistant d'aide : bulle « Besoin d'aide ? » et panneau de conversation.
 *
 * Masqué sans JavaScript (attribut hidden retiré par assets/js/app.js) : la page /help
 * reste alors le moyen d'accéder à l'aide. Les réponses viennent de POST /help/ask.
 *
 * data-chat-key : identifiant propre à la session. La conversation est gardée d'une page
 * à l'autre (sessionStorage), mais une nouvelle session (déconnexion) repart de zéro :
 * sur un poste partagé, l'utilisateur suivant ne voit pas la conversation précédente.
 */
use App\Core\Csrf;
?>
<div class="chatbot" data-chatbot hidden
     data-endpoint="<?= e(url('/help/ask')) ?>"
     data-csrf="<?= e(Csrf::token()) ?>"
     data-chat-key="<?= e(chat_key()) ?>"
     data-error="<?= e(t('bot.error')) ?>"
     data-you="<?= e(t('bot.you')) ?>"
     data-bot="<?= e(t('bot.name')) ?>">

    <section class="chatbot-panel" id="chatbot-panel" role="dialog" aria-labelledby="chatbot-title" data-chatbot-panel hidden>
        <header class="chatbot-head">
            <img src="<?= e(url('/assets/img/logo.svg')) ?>" alt="" width="32" height="32">
            <div class="chatbot-heading">
                <h2 id="chatbot-title"><?= e(t('bot.title')) ?></h2>
                <p><?= e(t('bot.subtitle')) ?></p>
            </div>
            <button class="chatbot-icon-btn" type="button" data-chatbot-restart title="<?= e(t('bot.restart')) ?>" aria-label="<?= e(t('bot.restart')) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><polyline points="3 3 3 9 9 9"/></svg>
            </button>
            <button class="chatbot-icon-btn" type="button" data-chatbot-close title="<?= e(t('bot.close')) ?>" aria-label="<?= e(t('bot.close')) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
            </button>
        </header>

        <ol class="chatbot-log" role="log" aria-live="polite" aria-labelledby="chatbot-title" data-chatbot-log></ol>

        <form class="chatbot-form" data-chatbot-form>
            <label class="sr-only" for="chatbot-input"><?= e(t('bot.input_label')) ?></label>
            <input type="text" id="chatbot-input" maxlength="300" autocomplete="off"
                   placeholder="<?= e(t('bot.placeholder')) ?>" data-chatbot-input>
            <button class="btn btn-primary chatbot-send" type="submit" aria-label="<?= e(t('bot.send')) ?>" title="<?= e(t('bot.send')) ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
    </section>

    <button class="chatbot-launcher" type="button" aria-expanded="false" aria-controls="chatbot-panel" data-chatbot-toggle>
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span class="chatbot-launcher-text"><?= e(t('bot.launcher')) ?></span>
        <span class="sr-only"><?= e(t('bot.open')) ?></span>
    </button>
</div>
