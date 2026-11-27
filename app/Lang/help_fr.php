<?php
/**
 * Contenu de la page d'aide (FAQ) — Français.
 *
 * Structure : sections > questions > blocs de réponse.
 *   'id'     : ancre de l'URL (/help#ecran-noir), à garder identique dans help_en.php
 *   'ticket'   : [catégorie, priorité] proposées si le problème persiste
 *   'keywords' : mots que tape un utilisateur pressé ; l'assistant s'en sert pour trouver la réponse
 * Blocs : ['p', texte] paragraphe · ['steps', [...]] étapes numérotées · ['list', [...]] liste
 *         ['note', texte] encadré d'information · ['warn', texte] encadré d'avertissement
 * Dans un texte, [[Ctrl]] s'affiche comme une touche du clavier.
 * Le texte est échappé à l'affichage : aucun HTML n'est interprété.
 */
return [
    [
        'id'    => 'premiers-pas',
        'title' => 'Premiers pas',
        'items' => [
            [
                'id' => 'connexion',
                'keywords' => ['se connecter', 'connecter', 'identifiant', 'login', 'accéder au portail', 'accès'],
                'q'  => 'Comment me connecter au portail ?',
                'a'  => [
                    ['p', 'Saisissez votre identifiant (par exemple marie.dupont) et le mot de passe que vous a remis un référent numérique, puis cliquez sur « Se connecter ». Votre adresse e-mail ENT ne sert qu’à recevoir les notifications.'],
                    ['p', 'Le bouton en forme d’œil, dans le champ du mot de passe, affiche ce que vous tapez : pratique pour repérer une faute de frappe.'],
                    ['note', 'Par sécurité, vous êtes déconnecté après 30 minutes sans activité, et un formulaire non envoyé est alors perdu. Envoyez votre déclaration avant de vous absenter.'],
                ],
            ],
            [
                'id' => 'premiere-connexion',
                'keywords' => ['première fois', 'mot de passe provisoire', 'nouveau compte', 'activer mon compte'],
                'q'  => 'C’est ma première connexion : que dois-je faire ?',
                'a'  => [
                    ['p', 'Votre mot de passe de départ est provisoire. Le portail vous demande d’en choisir un nouveau avant d’aller plus loin. Il doit contenir :'],
                    ['list', [
                        'au moins 12 caractères ;',
                        'au moins une minuscule, une majuscule et un chiffre.',
                    ]],
                    ['p', 'Il doit aussi être différent de votre identifiant, de votre adresse e-mail et du mot de passe provisoire.'],
                    ['note', 'Astuce : trois mots sans rapport entre eux, reliés par des tirets et complétés d’un chiffre, donnent un mot de passe facile à retenir et difficile à deviner.'],
                ],
            ],
            [
                'id' => 'mot-de-passe-oublie',
                'keywords' => ['oublié', 'bloqué', 'verrouillé', 'réinitialiser', 'plus accès', 'trop d’essais', 'mot de passe oublié', 'nouveau mot de passe', 'mot de passe perdu', 'perdu mon mot de passe'],
                'q'  => 'J’ai oublié mon mot de passe, ou mon compte est bloqué',
                'a'  => [
                    ['p', 'Après 5 essais erronés, le compte est bloqué pendant 15 minutes : c’est une protection contre les tentatives d’intrusion. Patientez, puis réessayez.'],
                    ['p', 'Si vous avez oublié votre mot de passe, cliquez sur « Mot de passe oublié ? » sur la page de connexion, puis saisissez votre identifiant (ou votre adresse e-mail ENT si vous l’avez oublié). Vous pouvez indiquer où vous joindre (salle, poste téléphonique).'],
                    ['p', 'Un administrateur est prévenu. Après avoir vérifié votre identité, il vous remet un mot de passe provisoire, en main propre ou par téléphone ; vous le changez à la connexion suivante. Si vous retrouvez votre mot de passe entre-temps, connectez-vous normalement : la demande est annulée.'],
                    ['warn', 'Personne au lycée ne vous demandera votre mot de passe, ni par e-mail, ni par téléphone. Ne le communiquez jamais.'],
                ],
            ],
            [
                'id' => 'changer-mot-de-passe',
                'keywords' => ['changer', 'modifier', 'nouveau mot de passe', 'mon compte'],
                'q'  => 'Comment changer mon mot de passe ?',
                'a'  => [
                    ['steps', [
                        'Cliquez sur vos initiales, en haut à droite de la page.',
                        'Dans « Changer mon mot de passe », saisissez le mot de passe actuel, puis le nouveau, deux fois.',
                        'Cliquez sur « Changer le mot de passe ».',
                    ]],
                ],
            ],
            [
                'id' => 'langue',
                'keywords' => ['anglais', 'english', 'français', 'langue', 'traduction', 'site en anglais', 'interface'],
                'q'  => 'Comment afficher le portail en anglais ?',
                'a'  => [
                    ['p', 'Choisissez « English » dans le menu de langue, en haut de la page. Sur téléphone, ce menu se trouve sous le bouton « Menu ».'],
                    ['p', 'Votre choix est mémorisé sur votre compte : il s’applique aussi aux e-mails que le portail vous envoie.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'declarer-et-suivre',
        'title' => 'Déclarer et suivre un incident',
        'items' => [
            [
                'id' => 'declarer',
                'keywords' => ['déclarer', 'signaler', 'créer un ticket', 'nouveau ticket', 'faire un ticket', 'ouvrir un ticket'],
                'q'  => 'Comment déclarer un incident ?',
                'a'  => [
                    ['steps', [
                        'Cliquez sur « Déclarer un incident », en haut de la page.',
                        'Donnez un objet court et précis : « Vidéoprojecteur de la A102 sans image » plutôt que « Problème ».',
                        'Choisissez la catégorie, puis indiquez la salle ou le bureau concerné : les lieux connus vous sont proposés pendant la saisie.',
                        'Choisissez la priorité (voir la question suivante).',
                        'Décrivez le problème : ce qui se passe, depuis quand, sur quel équipement, et le message d’erreur s’il y en a un.',
                        'Si possible, joignez une photo ou une capture d’écran : JPG, PNG ou PDF, 5 Mo au maximum.',
                        'Cliquez sur « Déclarer l’incident ».',
                    ]],
                    ['p', 'Le ticket reçoit une référence, par exemple TT-2026-00042, et vous recevez un e-mail de confirmation. Le support est prévenu au même moment.'],
                ],
            ],
            [
                'id' => 'priorite',
                'keywords' => ['priorité', 'urgent', 'urgence', 'critique', 'délai', 'combien de temps'],
                'q'  => 'Quelle priorité choisir ?',
                'a'  => [
                    ['list', [
                        'Basse : une question ou une gêne mineure, rien n’est bloqué. Résolution visée sous 72 h.',
                        'Moyenne : une panne isolée, avec une solution de repli (changer de poste, par exemple). Résolution visée sous 48 h.',
                        'Haute : un cours ou une salle entière est bloqué. Résolution visée sous 12 h.',
                        'Critique : le réseau ou un service est indisponible pour tout l’établissement. Résolution visée sous 4 h.',
                    ]],
                    ['note', 'Surestimer la priorité retarde les vraies urgences de vos collègues. En cas de doute, choisissez « Moyenne » et expliquez la gêne dans la description.'],
                ],
            ],
            [
                'id' => 'bonne-description',
                'keywords' => ['description', 'décrire', 'rédiger', 'expliquer', 'détails'],
                'q'  => 'Comment rédiger une description utile ?',
                'a'  => [
                    ['p', 'Une description précise évite un aller-retour de questions et accélère la réparation. Indiquez :'],
                    ['list', [
                        'ce que vous constatez exactement : « l’écran reste noir », « message Licence expirée » ;',
                        'depuis quand, et si le problème est permanent ou intermittent ;',
                        'l’équipement précis : numéro du poste (étiquette sur l’unité centrale), modèle de l’appareil ;',
                        'ce que vous avez déjà essayé : redémarrage, autre prise… ;',
                        'les créneaux où la salle est libre pour une intervention.',
                    ]],
                    ['note', 'Une panne, un ticket. Si deux appareils sont en panne, déclarez deux tickets : chacun sera suivi et clôturé séparément.'],
                ],
            ],
            [
                'id' => 'suivre',
                'keywords' => ['suivre', 'suivi', 'où en est', 'avancement', 'mes tickets', 'historique'],
                'q'  => 'Comment suivre mes tickets ?',
                'a'  => [
                    ['p', 'La page « Mes tickets » liste toutes vos déclarations. Les onglets « En cours » et « Terminés » permettent de les filtrer. Cliquez sur un ticket pour voir son avancement, les messages du support et son historique.'],
                    ['p', 'Vous recevez aussi un e-mail à chaque étape : prise en charge, question du support, résolution, clôture.'],
                ],
            ],
            [
                'id' => 'statuts',
                'keywords' => ['statut', 'état', 'signification', 'nouveau', 'en cours', 'clôturé', 'rejeté'],
                'q'  => 'Que signifient les statuts ?',
                'a'  => [
                    ['list', [
                        'Nouveau : le ticket est enregistré, aucun technicien ne l’a encore pris en charge.',
                        'En cours : un technicien s’en occupe.',
                        'En attente : le support attend une réponse de votre part.',
                        'Résolu : le support a réglé le problème et attend votre confirmation.',
                        'Clôturé : le ticket est terminé.',
                        'Rejeté : le support ne donnera pas suite ; le motif figure dans les messages du ticket.',
                    ]],
                ],
            ],
            [
                'id' => 'en-attente',
                'keywords' => ['en attente', 'répondre', 'question du support', 'attend ma réponse'],
                'q'  => 'Mon ticket est « En attente » : que faire ?',
                'a'  => [
                    ['p', 'Le support vous a posé une question : elle figure dans les messages du ticket et vous l’avez reçue par e-mail. Répondez dans le champ « Ajouter un message » : le ticket repasse automatiquement « En cours ».'],
                    ['note', 'Tant que vous n’avez pas répondu, la réparation ne peut pas avancer. Un bandeau en haut de « Mes tickets » rappelle les tickets qui attendent une action de votre part.'],
                ],
            ],
            [
                'id' => 'resolu',
                'keywords' => ['résolu', 'confirmer', 'valider la résolution', 'toujours en panne', 'pas réparé'],
                'q'  => 'Mon ticket est « Résolu » : que dois-je faire ?',
                'a'  => [
                    ['list', [
                        'Le problème est réglé : cliquez sur « Oui, clôturer le ticket ».',
                        'Le problème persiste : cliquez sur « Non, le problème persiste » et expliquez ce qui ne fonctionne toujours pas. Le ticket repart chez le technicien.',
                    ]],
                    ['p', 'Sans réponse de votre part, le ticket est clôturé automatiquement 7 jours après sa résolution.'],
                ],
            ],
            [
                'id' => 'completer',
                'keywords' => ['ajouter', 'compléter', 'photo', 'pièce jointe', 'précision', 'information'],
                'q'  => 'Comment ajouter une information après ma déclaration ?',
                'a'  => [
                    ['p', 'Ouvrez le ticket et écrivez dans « Ajouter un message ». Le technicien en charge est prévenu par e-mail.'],
                    ['note', 'Une pièce jointe ne peut être ajoutée qu’au moment de la déclaration. Si une photo vous manque après coup, décrivez dans un message ce qu’elle montre.'],
                ],
            ],
            [
                'id' => 'probleme-revient',
                'keywords' => ['revient', 'encore', 'à nouveau', 'récidive', 'ticket clôturé', 'rouvrir'],
                'q'  => 'Le problème revient alors que le ticket est clôturé',
                'a'  => [
                    ['p', 'Un ticket clôturé ou rejeté ne peut plus être modifié. Déclarez un nouvel incident en indiquant la référence de l’ancien ticket dans la description : le support fera le lien, et la panne sera comptée comme récurrente dans les statistiques.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'bons-reflexes',
        'title' => 'Avant de déclarer : les bons réflexes',
        'items' => [
            [
                'id' => 'danger',
                'keywords' => ['fumée', 'feu', 'incendie', 'brûlé', 'étincelles', 'électrocution', 'fuite d’eau', 'danger'],
                'q'  => 'Il y a un danger : fumée, odeur de brûlé, étincelles, eau',
                'a'  => [
                    ['warn', 'Ne touchez pas à l’appareil, éloignez les élèves et prévenez immédiatement la vie scolaire ou l’administration. Ne tentez aucune réparation. La déclaration sur le portail vient ensuite, pour garder une trace.'],
                ],
            ],
            [
                'id' => 'verifications',
                'keywords' => ['vérifier', 'conseils', 'astuces', 'redémarrer', 'capture d’écran', 'avant de déclarer'],
                'q'  => 'Quelles vérifications faire avant de déclarer une panne ?',
                'a'  => [
                    ['p', 'Ces trois gestes règlent une bonne partie des petites pannes :'],
                    ['steps', [
                        'Redémarrez l’appareil, après avoir enregistré votre travail.',
                        'Vérifiez que les câbles sont bien branchés aux deux bouts et que la multiprise est allumée.',
                        'Essayez sur un autre poste ou une autre prise, pour savoir si le problème vient de l’appareil ou de la salle.',
                    ]],
                    ['p', 'Notez aussi le message d’erreur exact. Pour en faire une capture sous Windows, appuyez sur [[Windows]] + [[Maj]] + [[S]], ou prenez simplement l’écran en photo avec votre téléphone.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'ordinateurs',
        'title' => 'Ordinateurs et périphériques',
        'items' => [
            [
                'id'     => 'pc-ne-demarre-pas',
                'keywords' => ['allume pas', 'démarre pas', 'éteint', 'alimentation', 'unité centrale', 'bouton marche'],
                'q'      => 'L’ordinateur ne s’allume pas',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez que la multiprise est allumée (son interrupteur est souvent rouge) et branchée au mur.',
                        'Vérifiez que le câble d’alimentation est bien enfoncé à l’arrière de l’unité centrale.',
                        'Appuyez une fois franchement sur le bouton de démarrage, puis patientez 30 secondes.',
                        'Essayez une autre prise murale.',
                    ]],
                ],
            ],
            [
                'id'     => 'ecran-noir',
                'keywords' => ['écran noir', 'pas de signal', 'moniteur', 'écran éteint', 'affichage'],
                'q'      => 'L’ordinateur tourne mais l’écran reste noir',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez que l’écran est allumé : il a son propre bouton et son propre voyant.',
                        'Bougez la souris ou appuyez sur une touche : le poste est peut-être en veille.',
                        'Vérifiez le câble entre l’écran et l’unité centrale (HDMI, DisplayPort ou VGA), aux deux bouts.',
                        'Si l’écran affiche « Pas de signal », utilisez son bouton « Source » ou « Input » pour choisir la bonne entrée.',
                    ]],
                ],
            ],
            [
                'id'     => 'clavier-souris',
                'keywords' => ['clavier', 'souris', 'touches', 'curseur', 'usb', 'sans fil'],
                'q'      => 'Le clavier ou la souris ne répond plus',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Débranchez puis rebranchez le câble USB, si possible sur un autre port.',
                        'Pour un modèle sans fil, vérifiez son interrupteur et changez les piles.',
                        'Redémarrez l’ordinateur.',
                    ]],
                    ['note', 'Pour redémarrer sans souris : appuyez sur [[Ctrl]] + [[Alt]] + [[Suppr]], puis atteignez le bouton d’arrêt avec la touche [[Tab]] et les flèches.'],
                ],
            ],
            [
                'id'     => 'lent-ou-fige',
                'keywords' => ['lent', 'rame', 'figé', 'freeze', 'bloqué', 'ne répond plus', 'mise à jour'],
                'q'      => 'L’ordinateur est très lent ou figé',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Patientez une minute : une mise à jour ou une sauvegarde peut ralentir le poste.',
                        'Fermez les logiciels et les onglets dont vous n’avez pas besoin.',
                        'Si un logiciel ne répond plus, appuyez sur [[Ctrl]] + [[Maj]] + [[Échap]] pour ouvrir le Gestionnaire des tâches, sélectionnez le logiciel, puis cliquez sur « Fin de tâche ».',
                        'En dernier recours, redémarrez le poste.',
                    ]],
                    ['warn', 'Si l’écran indique qu’une mise à jour est en cours, n’éteignez pas l’ordinateur : vous risqueriez de le rendre inutilisable.'],
                ],
            ],
            [
                'id'     => 'imprimante',
                'keywords' => ['imprimer', 'impression', 'bourrage', 'papier', 'toner', 'encre', 'cartouche', 'photocopieuse'],
                'q'      => 'L’imprimante n’imprime pas',
                'ticket' => [1, 'low'],
                'a'      => [
                    ['steps', [
                        'Vérifiez qu’elle est allumée, sans voyant d’erreur, et qu’il reste du papier.',
                        'En cas de bourrage, suivez les indications de son écran pour ouvrir le bon volet, et retirez la feuille doucement, sans la déchirer.',
                        'Sur l’ordinateur, vérifiez que vous imprimez vers la bonne imprimante.',
                        'Relancez l’impression une seule fois : plusieurs envois successifs encombrent la file d’impression.',
                    ]],
                    ['note', 'Toner ou cartouche vide : déclarez un incident de priorité Basse, en précisant le modèle de l’imprimante et la salle.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'reseau',
        'title' => 'Réseau, Wi-Fi et Internet',
        'items' => [
            [
                'id'     => 'pas-internet',
                'keywords' => ['internet', 'réseau', 'câble réseau', 'rj45', 'filaire', 'hors ligne'],
                'q'      => 'Pas d’Internet sur un ordinateur fixe',
                'ticket' => [2, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez que le câble réseau est bien clipsé dans l’ordinateur et dans la prise murale : vous devez entendre un clic.',
                        'Regardez si les autres postes de la salle ont Internet.',
                        'Si un seul poste est touché, redémarrez-le.',
                    ]],
                    ['p', 'Si toute la salle est privée d’Internet, déclarez un incident de priorité Haute.'],
                ],
            ],
            [
                'id'     => 'wifi',
                'keywords' => ['wifi', 'sans fil', 'borne', 'mode avion', 'tablette', 'téléphone'],
                'q'      => 'Je n’arrive pas à me connecter au Wi-Fi',
                'ticket' => [2, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez que le Wi-Fi est activé et que le mode avion est désactivé.',
                        'Vérifiez que vous choisissez bien le réseau de l’établissement.',
                        'Supprimez ce réseau de la liste des réseaux enregistrés (« Oublier ce réseau »), puis reconnectez-vous avec vos identifiants.',
                        'Rapprochez-vous de la borne Wi-Fi, ou essayez depuis une autre salle.',
                    ]],
                    ['p', 'Si personne n’arrive à se connecter dans cette zone, déclarez un incident en précisant le lieu exact.'],
                ],
            ],
            [
                'id'     => 'ent-ou-site',
                'keywords' => ['ent', 'site', 'pronote', 'navigateur', 'page web', 'inaccessible'],
                'q'      => 'L’ENT ou un site web ne s’ouvre pas',
                'ticket' => [2, 'high'],
                'a'      => [
                    ['steps', [
                        'Ouvrez un autre site connu : s’il s’affiche, Internet fonctionne et le problème vient du site lui-même.',
                        'Rechargez la page, ou ouvrez-la dans une fenêtre de navigation privée : [[Ctrl]] + [[Maj]] + [[N]] dans Chrome et Edge.',
                        'Pour l’ENT, vérifiez vos identifiants : ce sont ceux de l’ENT, pas ceux de ce portail.',
                    ]],
                    ['note', 'Si un service en ligne est en panne pour tout le monde, le support ne peut souvent qu’attendre son rétablissement. Déclarez-le tout de même s’il bloque vos cours : le support saura que l’établissement est touché.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'logiciels',
        'title' => 'Logiciels et fichiers',
        'items' => [
            [
                'id'     => 'logiciel-plante',
                'keywords' => ['logiciel', 'application', 'plante', 'crash', 'se ferme', 'ne se lance', 'bug', 'erreur'],
                'q'      => 'Un logiciel ne se lance pas ou se ferme tout seul',
                'ticket' => [3, 'medium'],
                'a'      => [
                    ['steps', [
                        'Fermez complètement le logiciel, puis relancez-le.',
                        'Redémarrez l’ordinateur.',
                        'Essayez sur un autre poste : si le logiciel y fonctionne, précisez-le dans votre déclaration.',
                    ]],
                    ['p', 'Notez le message d’erreur exact, ou faites-en une capture : c’est l’information la plus utile au technicien.'],
                ],
            ],
            [
                'id'     => 'licence',
                'keywords' => ['licence', 'expirée', 'activation', 'clé de licence', 'invalide'],
                'q'      => 'Message « licence expirée » ou « licence invalide »',
                'ticket' => [3, 'high'],
                'a'      => [
                    ['p', 'Ce problème se règle sur le serveur de licences : vous ne pouvez rien y faire depuis le poste. N’essayez pas de réinstaller le logiciel.'],
                    ['p', 'Déclarez un incident en recopiant le message exact. Si tous les postes de la salle sont touchés et qu’un cours en dépend, choisissez la priorité Haute.'],
                ],
            ],
            [
                'id'     => 'fichier-perdu',
                'keywords' => ['fichier', 'document', 'perdu', 'supprimé', 'enregistrer', 'récupérer', 'corbeille', 'word', 'excel'],
                'q'      => 'J’ai perdu un fichier ou oublié de l’enregistrer',
                'ticket' => [3, 'medium'],
                'a'      => [
                    ['steps', [
                        'Cherchez dans les fichiers récents du logiciel : menu « Fichier », puis « Ouvrir » ou « Récent ».',
                        'Regardez dans la corbeille.',
                        'Dans Word, Excel ou PowerPoint : « Fichier », « Informations », « Gérer le document », puis « Récupérer les documents non enregistrés ».',
                    ]],
                    ['note', 'Sur les postes partagés, enregistrez vos documents dans votre espace personnel ou sur une clé USB plutôt que sur le Bureau : le Bureau d’un poste partagé n’est pas un endroit sûr pour vos fichiers.'],
                ],
            ],
            [
                'id'     => 'installer-logiciel',
                'keywords' => ['installer', 'installation', 'télécharger', 'nouveau logiciel'],
                'q'      => 'J’ai besoin d’un logiciel qui n’est pas installé',
                'ticket' => [3, 'low'],
                'a'      => [
                    ['p', 'Ne téléchargez pas de logiciel vous-même : les installations passent par les référents numériques, qui vérifient la licence et la sécurité.'],
                    ['p', 'Déclarez une demande en priorité Basse, en indiquant le nom du logiciel, les salles concernées et la date à laquelle vous en avez besoin. Anticipez : une installation sur toute une salle prend du temps.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'audiovisuel',
        'title' => 'Vidéoprojecteurs, son et tableaux interactifs',
        'items' => [
            [
                'id'     => 'projecteur-sans-image',
                'keywords' => ['vidéoprojecteur', 'projo', 'pas d’image', 'rien ne s’affiche', 'télécommande', 'hdmi', 'voyant rouge', 'lampe'],
                'q'      => 'Le vidéoprojecteur n’affiche rien',
                'ticket' => [4, 'high'],
                'a'      => [
                    ['steps', [
                        'Allumez-le avec la télécommande ou son bouton, puis patientez environ une minute : la lampe doit chauffer.',
                        'Choisissez la bonne entrée avec le bouton « Source » ou « Input » : HDMI 1, HDMI 2, VGA…',
                        'Vérifiez que le câble est branché à l’ordinateur et au boîtier mural.',
                        'Sur l’ordinateur, appuyez sur [[Windows]] + [[P]] et choisissez « Dupliquer ».',
                        'Si la télécommande ne réagit pas, changez ses piles et visez le capteur du vidéoprojecteur.',
                    ]],
                    ['warn', 'Si un voyant rouge clignote, ne l’éteignez pas et ne le rallumez pas plusieurs fois de suite : il a besoin de refroidir. Déclarez l’incident en indiquant la couleur et le rythme du voyant.'],
                ],
            ],
            [
                'id'     => 'image-floue',
                'keywords' => ['flou', 'netteté', 'mise au point', 'image coupée', 'déformée', 'résolution'],
                'q'      => 'L’image est floue, coupée ou déformée',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Réglez la netteté avec la bague de mise au point de l’objectif, ou depuis la télécommande.',
                        'Si l’image est coupée ou déformée, faites un clic droit sur le Bureau, choisissez « Paramètres d’affichage » et essayez une résolution plus basse.',
                    ]],
                ],
            ],
            [
                'id'     => 'pas-de-son',
                'keywords' => ['son', 'audio', 'haut-parleur', 'enceintes', 'volume', 'muet'],
                'q'      => 'Pas de son',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez le volume de l’ordinateur, avec l’icône du haut-parleur, et qu’il n’est pas coupé.',
                        'Vérifiez que les enceintes sont allumées et que leur propre volume est monté.',
                        'Cliquez sur l’icône du haut-parleur et choisissez la bonne sortie audio : enceintes, vidéoprojecteur ou écran.',
                        'Vérifiez le câble audio : prise jack verte, ou câble HDMI.',
                    ]],
                ],
            ],
            [
                'id'     => 'tableau-interactif',
                'keywords' => ['tbi', 'vpi', 'tni', 'tableau', 'stylet', 'interactif', 'tactile', 'calibrage', 'bloqué', 'figé'],
                'q'      => 'Le tableau ou le stylet interactif ne répond plus',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Vérifiez la charge ou les piles du stylet.',
                        'Vérifiez que le câble USB du tableau est bien branché à l’ordinateur.',
                        'Lancez l’outil de calibrage (ou « d’orientation ») du logiciel du tableau et suivez les étapes.',
                        'Redémarrez l’ordinateur, tableau branché.',
                    ]],
                ],
            ],
        ],
    ],

    [
        'id'    => 'batiment',
        'title' => 'Bâtiment et équipements',
        'items' => [
            [
                'id'     => 'eclairage',
                'keywords' => ['lumière', 'néon', 'éclairage', 'clignote', 'ampoule', 'plafonnier'],
                'q'      => 'Une lumière ne s’allume pas ou clignote',
                'ticket' => [5, 'low'],
                'a'      => [
                    ['p', 'Vérifiez les autres interrupteurs de la salle. Ne démontez rien, et ne montez ni sur une table ni sur une chaise pour atteindre un luminaire.'],
                    ['p', 'Déclarez l’incident en précisant la position du luminaire, par exemple « au fond, côté fenêtres ».'],
                ],
            ],
            [
                'id'     => 'prise-electrique',
                'keywords' => ['prise électrique', 'courant', 'électricité', 'multiprise', 'disjoncteur'],
                'q'      => 'Une prise électrique ne fonctionne pas',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['steps', [
                        'Branchez un autre appareil pour vérifier que la prise est bien en cause.',
                        'Si vous utilisez une multiprise, vérifiez son interrupteur.',
                    ]],
                    ['warn', 'N’intervenez jamais sur le tableau électrique et ne réarmez pas un disjoncteur vous-même : prévenez l’administration.'],
                ],
            ],
            [
                'id'     => 'chauffage-fenetre',
                'keywords' => ['chauffage', 'radiateur', 'froid', 'chaud', 'store', 'volet', 'fenêtre', 'porte', 'serrure'],
                'q'      => 'Chauffage, store, fenêtre ou porte en panne',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['p', 'Déclarez un incident en précisant l’équipement et la salle. Une fenêtre qui ne ferme plus ou une porte bloquée peut poser un problème de sécurité : choisissez alors la priorité Haute.'],
                ],
            ],
            [
                'id'     => 'mobilier',
                'keywords' => ['chaise', 'table', 'meuble', 'cassé', 'casier', 'armoire'],
                'q'      => 'Mobilier cassé ou dangereux',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['p', 'Mettez la table ou la chaise à l’écart pour que personne ne s’en serve, puis déclarez l’incident avec une photo.'],
                ],
            ],
        ],
    ],
];
