/**
 * Ticket'Tournelle — interactivité (JavaScript sans bibliothèque).
 *
 * Principe : tout fonctionne sans JavaScript ; ce script ajoute du confort.
 * Les comportements se branchent sur des attributs data-* posés dans les vues,
 * jamais sur du JavaScript écrit dans le HTML (interdit par la politique CSP).
 */
(function () {
    'use strict';

    // Signale au CSS que JavaScript est actif (masque les boutons devenus inutiles)
    document.documentElement.classList.add('js');

    /* ------------------------------------------------------------------
       1. Envoi automatique : sélecteur de langue, filtres de la file d'attente
       ------------------------------------------------------------------ */
    document.querySelectorAll('[data-autosubmit]').forEach(function (field) {
        field.addEventListener('change', function () {
            field.form.submit();
        });
    });

    /* ------------------------------------------------------------------
       2. Barre de navigation : affichage adapté à la place disponible
          On essaie d'abord la barre complète, puis sans le nom sous
          l'avatar, puis repliée derrière le bouton « Menu ». La mesure est
          réelle : elle tient compte du rôle (nombre d'entrées), de la langue
          et de la barre de défilement du navigateur.
       ------------------------------------------------------------------ */
    var topbar = document.querySelector('.topbar-inner');
    var toggle = document.querySelector('[data-nav-toggle]');
    var menu = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;

    if (topbar && menu) {
        var rootClasses = document.documentElement.classList;
        var overflows = function () {
            return topbar.scrollWidth > topbar.clientWidth;
        };
        var fitTopbar = function () {
            rootClasses.remove('nav-compact', 'nav-collapsed');
            if (overflows()) {
                rootClasses.add('nav-compact');
                if (overflows()) {
                    rootClasses.add('nav-collapsed');
                }
            }
            // Menu replié refermé si la barre complète redevient possible
            if (!rootClasses.contains('nav-collapsed')) {
                menu.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        };

        toggle.addEventListener('click', function () {
            var open = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });

        var pending = false;
        window.addEventListener('resize', function () {
            if (!pending) {
                pending = true;
                window.requestAnimationFrame(function () {
                    pending = false;
                    fitTopbar();
                });
            }
        });
        fitTopbar();
    }

    /* ------------------------------------------------------------------
       4. Compteur de caractères des zones de texte
       ------------------------------------------------------------------ */
    document.querySelectorAll('[data-counter]').forEach(function (textarea) {
        var output = document.getElementById(textarea.dataset.counter);
        var update = function () {
            output.textContent = textarea.value.length + ' / ' + textarea.maxLength;
        };
        textarea.addEventListener('input', update);
        update();
    });

    /* ------------------------------------------------------------------
       5. Pièce jointe : contrôle de taille avant l'envoi
          (simple confort : le serveur revérifie toujours taille et type)
       ------------------------------------------------------------------ */
    document.querySelectorAll('input[type="file"][data-max-size]').forEach(function (input) {
        var message = input.parentNode.querySelector('[data-file-error]');
        input.addEventListener('change', function () {
            var file = input.files[0];
            var tooLarge = Boolean(file) && file.size > Number(input.dataset.maxSize);
            if (tooLarge) {
                input.value = '';
                message.textContent = input.dataset.tooLarge;
            }
            message.hidden = !tooLarge;
        });
    });

    /* ------------------------------------------------------------------
       6. Technicien : le message devient obligatoire selon le statut choisi
       ------------------------------------------------------------------ */
    var statusSelect = document.querySelector('[data-status-select]');
    var statusNote = document.querySelector('[data-status-note]');
    if (statusSelect && statusNote) {
        var required = statusSelect.dataset.noteRequired.split(',');
        var syncNote = function () {
            statusNote.required = required.indexOf(statusSelect.value) !== -1;
        };
        statusSelect.addEventListener('change', syncNote);
        syncNote();
    }

    /* ------------------------------------------------------------------
       7. Administration : le niveau de support ne concerne que les techniciens
       ------------------------------------------------------------------ */
    document.querySelectorAll('[data-role-select]').forEach(function (roleSelect) {
        // Le menu « niveau » associé est dans la même ligne de tableau ou le même formulaire
        var scope = roleSelect.closest('tr') || roleSelect.closest('form');
        var levelSelect = scope ? scope.querySelector('[data-level-select]') : null;
        if (!levelSelect) {
            return;
        }
        var syncLevel = function () {
            var code = roleSelect.options[roleSelect.selectedIndex].dataset.code;
            levelSelect.disabled = code !== 'technician';
        };
        roleSelect.addEventListener('change', syncLevel);
        syncLevel();
    });

    /* ------------------------------------------------------------------
       8. Afficher / masquer un mot de passe
          Un bouton est ajouté dans chaque champ mot de passe du site
          (connexion, Mon compte, administration). Sans JavaScript, les
          champs restent de simples champs masqués.
       ------------------------------------------------------------------ */
    var root = document.documentElement;
    var showLabel = root.dataset.passwordShow || 'Show password';
    var hideLabel = root.dataset.passwordHide || 'Hide password';

    // Icônes « œil » et « œil barré », décoratives : le texte du bouton est dans aria-label
    var ICON_SHOW = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        + '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    var ICON_HIDE = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        + '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>'
        + '<path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>'
        + '<path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        // Le champ est placé dans un conteneur qui accueille aussi le bouton
        var wrapper = document.createElement('span');
        wrapper.className = 'password-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var button = document.createElement('button');
        button.type = 'button';                       // ne doit jamais envoyer le formulaire
        button.className = 'password-toggle';
        if (input.id) {
            button.setAttribute('aria-controls', input.id);
        }
        wrapper.appendChild(button);

        var render = function (visible) {
            input.type = visible ? 'text' : 'password';
            button.innerHTML = visible ? ICON_HIDE : ICON_SHOW;
            button.setAttribute('aria-label', visible ? hideLabel : showLabel);
            button.title = visible ? hideLabel : showLabel;
        };

        button.addEventListener('click', function () {
            render(input.type === 'password');
        });
        render(false);

        // Le mot de passe est remasqué à l'envoi : le navigateur ne le garde
        // pas en clair dans l'historique de saisie des champs texte
        if (input.form) {
            input.form.addEventListener('submit', function () {
                render(false);
            });
        }
    });

    /* ------------------------------------------------------------------
       9. Page d'aide : recherche dans les questions, et ouverture de la
          réponse visée par un lien (/help#ecran-noir)
       ------------------------------------------------------------------ */
    var faq = document.querySelector('[data-faq]');
    if (faq) {
        var items = Array.prototype.slice.call(faq.querySelectorAll('[data-faq-item]'));
        var sections = Array.prototype.slice.call(faq.querySelectorAll('[data-faq-section]'));
        var emptyBox = faq.querySelector('[data-faq-empty]');
        var searchField = document.querySelector('[data-faq-search]');
        var results = document.querySelector('[data-faq-results]');

        // Comparaison sans majuscules ni accents : « video » trouve « Vidéoprojecteur »
        var normalize = function (text) {
            return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        };
        items.forEach(function (item) {
            item.searchText = normalize(item.textContent);
        });

        var search = function () {
            var words = normalize(searchField.value).split(/\s+/).filter(Boolean);
            var found = 0;

            items.forEach(function (item) {
                // Tous les mots saisis doivent apparaître dans la question ou sa réponse
                var match = words.every(function (word) { return item.searchText.indexOf(word) !== -1; });
                item.hidden = !match;
                if (words.length) {
                    item.open = match;          // les réponses trouvées s'ouvrent directement
                }
                if (match) {
                    found++;
                }
            });
            sections.forEach(function (section) {
                section.hidden = !section.querySelector('[data-faq-item]:not([hidden])');
            });

            emptyBox.hidden = found > 0;
            results.textContent = words.length
                ? (found > 1 ? results.dataset.many : results.dataset.one).replace(':count', found)
                : '';
        };

        if (searchField) {
            document.querySelector('[data-faq-search-field]').hidden = false;
            searchField.addEventListener('input', search);
        }

        // Lien direct vers une réponse : on l'ouvre (un <details> fermé cache son contenu)
        var openFromHash = function () {
            var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
            if (target && target.matches('[data-faq-item]')) {
                target.open = true;
                target.scrollIntoView();
            }
        };
        window.addEventListener('hashchange', openFromHash);
        openFromHash();
    }

    /* ------------------------------------------------------------------
       10. Assistant d'aide (chatbot)
           Le serveur choisit les réponses dans la FAQ (POST /help/ask) et les
           renvoie en JSON ; ce script se contente de les afficher. Tout texte
           reçu est inséré avec textContent, jamais comme du HTML.
       ------------------------------------------------------------------ */
    var chatbot = document.querySelector('[data-chatbot]');
    if (chatbot && window.fetch) {
        var chatToggle  = chatbot.querySelector('[data-chatbot-toggle]');
        var chatPanel   = chatbot.querySelector('[data-chatbot-panel]');
        var chatLog     = chatbot.querySelector('[data-chatbot-log]');
        var chatForm    = chatbot.querySelector('[data-chatbot-form]');
        var chatInput   = chatbot.querySelector('[data-chatbot-input]');
        var chatKey     = 'tt-chat-' + chatbot.dataset.chatKey;
        var chatHistory = [];
        var chatBusy    = false;

        // --- Mémoire de la conversation d'une page à l'autre (session en cours seulement)
        var loadHistory = function () {
            try {
                // Conversations d'anciennes sessions (avant une déconnexion) : effacées
                for (var k = sessionStorage.length - 1; k >= 0; k--) {
                    var name = sessionStorage.key(k);
                    if (name.indexOf('tt-chat-') === 0 && name !== chatKey) {
                        sessionStorage.removeItem(name);
                    }
                }
                chatHistory = JSON.parse(sessionStorage.getItem(chatKey)) || [];
            } catch (e) {
                chatHistory = [];
            }
        };
        var saveHistory = function () {
            try {
                chatHistory = chatHistory.slice(-40);
                sessionStorage.setItem(chatKey, JSON.stringify(chatHistory));
            } catch (e) { /* stockage indisponible : la conversation n'est simplement pas gardée */ }
        };

        // --- Construction des éléments (jamais de innerHTML avec du contenu reçu)
        var make = function (tag, className, text) {
            var node = document.createElement(tag);
            if (className) { node.className = className; }
            if (text !== undefined) { node.textContent = text; }
            return node;
        };
        // « [[Ctrl]] » devient une touche de clavier, le reste reste du texte
        var richText = function (parent, text) {
            String(text).split(/\[\[(.+?)\]\]/).forEach(function (part, index) {
                parent.appendChild(index % 2 ? make('kbd', '', part) : document.createTextNode(part));
            });
            return parent;
        };
        var speaker = function (who) {
            return make('span', 'sr-only', chatbot.dataset[who] + ' ');
        };

        var renderUser = function (text) {
            var item = make('li', 'chat-msg from-user');
            var bubble = make('p', 'chat-bubble');
            bubble.appendChild(speaker('you'));
            bubble.appendChild(document.createTextNode(text));
            item.appendChild(bubble);
            chatLog.appendChild(item);
        };

        var renderAnswer = function (message) {
            var bubble = make('div', 'chat-bubble chat-answer' + (message.kind === 'danger' ? ' is-danger' : ''));
            bubble.appendChild(speaker('bot'));
            bubble.appendChild(make('p', 'chat-answer-title', message.title));
            (message.blocks || []).forEach(function (block) {
                var type = block[0], content = block[1];
                if (type === 'steps' || type === 'list') {
                    var list = make(type === 'steps' ? 'ol' : 'ul');
                    content.forEach(function (line) { list.appendChild(richText(make('li'), line)); });
                    bubble.appendChild(list);
                } else {
                    bubble.appendChild(richText(make('p', type === 'note' || type === 'warn' ? 'callout callout-' + type : ''), content));
                }
            });
            if (message.helpUrl) {
                var more = make('a', 'chat-more', message.helpLabel);
                more.href = message.helpUrl;
                bubble.appendChild(more);
            }
            return bubble;
        };

        var renderChips = function (chips) {
            if (!chips || !chips.length) { return; }
            var row = make('div', 'chat-chips');
            chips.forEach(function (chip) {
                var node;
                if (chip.href) {
                    node = make('a', 'chat-chip is-link' + (chip.primary ? ' is-primary' : ''), chip.label);
                    node.href = chip.href;
                } else {
                    node = make('button', 'chat-chip', chip.label);
                    node.type = 'button';
                    node.addEventListener('click', function () { send(chip.payload, chip.label); });
                }
                row.appendChild(node);
            });
            chatLog.appendChild(make('li', 'chat-msg from-bot chat-chips-row')).appendChild(row);
        };

        // Les boutons de réponse rapide ne restent actifs que sous le dernier message
        var renderBot = function (data, withChips) {
            (data.messages || []).forEach(function (message) {
                var item = make('li', 'chat-msg from-bot');
                if (message.kind === 'text') {
                    var bubble = make('p', 'chat-bubble');
                    bubble.appendChild(speaker('bot'));
                    bubble.appendChild(document.createTextNode(message.text));
                    item.appendChild(bubble);
                } else {
                    item.appendChild(renderAnswer(message));
                }
                chatLog.appendChild(item);
            });
            if (withChips) { renderChips(data.chips); }
        };

        var scrollDown = function () {
            chatLog.scrollTop = chatLog.scrollHeight;
        };
        // Une réponse longue s'affiche depuis son début (titre et première étape visibles),
        // une réponse courte fait simplement défiler jusqu'en bas
        var showFrom = function (node) {
            if (!node) { return; }
            chatLog.scrollTop = Math.min(node.offsetTop - 12, chatLog.scrollHeight - chatLog.clientHeight);
        };

        // --- Échange avec le serveur
        var send = function (payload, userText) {
            if (chatBusy) { return; }
            chatBusy = true;
            Array.prototype.forEach.call(chatLog.querySelectorAll('.chat-chips-row'), function (row) { row.remove(); });

            if (userText) {
                renderUser(userText);
                chatHistory.push({ role: 'user', text: userText });
            }
            var typing = make('li', 'chat-msg from-bot chat-typing');
            typing.appendChild(make('span', 'sr-only', chatbot.dataset.bot + ' …'));
            var dots = typing.appendChild(make('span', 'chat-dots'));
            dots.setAttribute('aria-hidden', 'true');
            dots.appendChild(make('i'));
            dots.appendChild(make('i'));
            dots.appendChild(make('i'));
            chatLog.appendChild(typing);
            scrollDown();

            var body = new URLSearchParams(payload);
            body.append('_csrf', chatbot.dataset.csrf);

            fetch(chatbot.dataset.endpoint, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).then(function (response) {
                var type = response.headers.get('Content-Type') || '';
                if (!response.ok || type.indexOf('application/json') === -1) {
                    throw new Error('Réponse inattendue');
                }
                return response.json();
            }).then(function (data) {
                typing.remove();
                var firstNew = chatLog.children.length;
                renderBot(data, true);
                chatHistory.push({ role: 'bot', data: data });
                saveHistory();
                showFrom(chatLog.children[firstNew]);
            }).catch(function () {
                typing.remove();
                renderBot({ messages: [{ kind: 'text', text: chatbot.dataset.error }], chips: [] }, false);
                scrollDown();
            }).then(function () {
                chatBusy = false;
            });
        };

        // --- Ouverture, fermeture, saisie
        var openChat = function () {
            chatPanel.hidden = false;
            chatbot.classList.add('is-open');
            chatToggle.setAttribute('aria-expanded', 'true');
            if (!chatHistory.length) { send({ start: '1' }); }
            scrollDown();
            chatInput.focus();
        };
        var closeChat = function () {
            chatPanel.hidden = true;
            chatbot.classList.remove('is-open');
            chatToggle.setAttribute('aria-expanded', 'false');
            chatToggle.focus();
        };

        chatToggle.addEventListener('click', function () {
            if (chatPanel.hidden) { openChat(); } else { closeChat(); }
        });
        chatbot.querySelector('[data-chatbot-close]').addEventListener('click', closeChat);
        chatPanel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { closeChat(); }
        });
        chatbot.querySelector('[data-chatbot-restart]').addEventListener('click', function () {
            chatHistory = [];
            saveHistory();
            chatLog.textContent = '';
            send({ start: '1' });
            chatInput.focus();
        });
        chatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var text = chatInput.value.trim();
            if (text && !chatBusy) {
                chatInput.value = '';
                send({ q: text }, text);
            }
        });

        // Bouton « Poser ma question à l'assistant » de la page d'aide
        var openWrap = document.querySelector('[data-chatbot-open-wrap]');
        if (openWrap) {
            openWrap.hidden = false;
            openWrap.querySelector('[data-chatbot-open]').addEventListener('click', openChat);
        }

        // --- Démarrage : conversation de la page précédente réaffichée
        loadHistory();
        chatHistory.forEach(function (entry, index) {
            if (entry.role === 'user') {
                renderUser(entry.text);
            } else {
                renderBot(entry.data, index === chatHistory.length - 1);
            }
        });
        chatbot.hidden = false;
    }
})();
