<?php
/**
 * Help page content (FAQ) — English.
 * Same structure and same 'id' values as help_fr.php (see that file for the format).
 */
return [
    [
        'id'    => 'premiers-pas',
        'title' => 'Getting started',
        'items' => [
            [
                'id' => 'connexion',
                'keywords' => ['sign in', 'log in', 'login', 'username', 'access the portal'],
                'q'  => 'How do I sign in to the portal?',
                'a'  => [
                    ['p', 'Enter your work e-mail address and the password given to you by a digital support officer, then click “Sign in”.'],
                    ['p', 'The eye-shaped button in the password field shows what you type: handy for spotting a typo.'],
                    ['note', 'For security, you are signed out after 30 minutes of inactivity, and any form you have not sent is lost. Send your report before stepping away.'],
                ],
            ],
            [
                'id' => 'premiere-connexion',
                'keywords' => ['first time', 'temporary password', 'new account', 'activate my account'],
                'q'  => 'This is my first sign-in: what should I do?',
                'a'  => [
                    ['p', 'Your initial password is temporary. The portal asks you to choose a new one before going any further. It must contain:'],
                    ['list', [
                        'at least 12 characters;',
                        'at least one lowercase letter, one uppercase letter and one digit.',
                    ]],
                    ['p', 'It must also differ from your e-mail address and from the temporary password.'],
                    ['note', 'Tip: three unrelated words joined by hyphens, plus a digit, make a password that is easy to remember and hard to guess.'],
                ],
            ],
            [
                'id' => 'mot-de-passe-oublie',
                'keywords' => ['forgot', 'forgotten', 'locked', 'reset', 'no access', 'too many attempts', 'forgot password', 'new password', 'lost password', 'lost my password'],
                'q'  => 'I forgot my password, or my account is locked',
                'a'  => [
                    ['p', 'After 5 wrong attempts, the account is locked for 15 minutes: this protects it against break-in attempts. Wait, then try again.'],
                    ['p', 'If you forgot your password, click “Forgot password?” on the sign-in page, then enter your e-mail address. You can say where you can be reached (room, phone extension).'],
                    ['p', 'An administrator is notified. After checking who you are, they give you a temporary password, in person or by phone; you change it at your next sign-in. If you remember your password in the meantime, just sign in: the request is cancelled.'],
                    ['warn', 'Nobody at the school will ever ask for your password, by e-mail or by phone. Never share it.'],
                ],
            ],
            [
                'id' => 'changer-mot-de-passe',
                'keywords' => ['change', 'modify', 'new password', 'my account'],
                'q'  => 'How do I change my password?',
                'a'  => [
                    ['steps', [
                        'Click your initials, at the top right of the page.',
                        'Under “Change my password”, enter your current password, then the new one twice.',
                        'Click “Change password”.',
                    ]],
                ],
            ],
            [
                'id' => 'langue',
                'keywords' => ['french', 'english', 'language', 'translation', 'site in french', 'interface'],
                'q'  => 'How do I switch the portal to French?',
                'a'  => [
                    ['p', 'Choose “Français” in the language menu at the top of the page. On a phone, this menu is under the “Menu” button.'],
                    ['p', 'Your choice is saved on your account: it also applies to the e-mails the portal sends you.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'declarer-et-suivre',
        'title' => 'Reporting and following an incident',
        'items' => [
            [
                'id' => 'declarer',
                'keywords' => ['report', 'create a ticket', 'new ticket', 'open a ticket', 'declare'],
                'q'  => 'How do I report an incident?',
                'a'  => [
                    ['steps', [
                        'Click “Report an incident” at the top of the page.',
                        'Give a short, precise subject: “No image on the A102 projector” rather than “Problem”.',
                        'Choose the category, then type the room or office concerned: known locations are suggested as you type.',
                        'Choose the priority (see the next question).',
                        'Describe the problem: what is happening, since when, on which equipment, and the error message if there is one.',
                        'If you can, attach a photo or a screenshot: JPG, PNG or PDF, 5 MB maximum.',
                        'Click “Report the incident”.',
                    ]],
                    ['p', 'The ticket gets a reference, for example TT-2026-00042, and you receive a confirmation e-mail. Support is notified at the same time.'],
                ],
            ],
            [
                'id' => 'priorite',
                'keywords' => ['priority', 'urgent', 'emergency', 'critical', 'deadline', 'how long'],
                'q'  => 'Which priority should I choose?',
                'a'  => [
                    ['list', [
                        'Low: a question or a minor inconvenience, nothing is blocked. Target resolution within 72 h.',
                        'Medium: an isolated failure with a workaround (using another computer, for example). Target resolution within 48 h.',
                        'High: a lesson or a whole room is blocked. Target resolution within 12 h.',
                        'Critical: the network or a service is down for the whole school. Target resolution within 4 h.',
                    ]],
                    ['note', 'Overstating the priority delays your colleagues’ real emergencies. If in doubt, choose “Medium” and explain the impact in the description.'],
                ],
            ],
            [
                'id' => 'bonne-description',
                'keywords' => ['description', 'describe', 'write', 'explain', 'details'],
                'q'  => 'How do I write a useful description?',
                'a'  => [
                    ['p', 'A precise description avoids back-and-forth questions and speeds up the repair. Include:'],
                    ['list', [
                        'exactly what you see: “the screen stays black”, “License expired message”;',
                        'since when, and whether the problem is permanent or intermittent;',
                        'the exact equipment: computer number (label on the tower), device model;',
                        'what you have already tried: restarting, another socket…;',
                        'the time slots when the room is free for a repair.',
                    ]],
                    ['note', 'One failure, one ticket. If two devices are broken, report two tickets: each one will be followed and closed separately.'],
                ],
            ],
            [
                'id' => 'suivre',
                'keywords' => ['follow', 'track', 'progress', 'my tickets', 'history', 'status of my ticket'],
                'q'  => 'How do I follow my tickets?',
                'a'  => [
                    ['p', 'The “My tickets” page lists all your reports. The “Active” and “Done” tabs filter them. Click a ticket to see its progress, messages from support and its history.'],
                    ['p', 'You also receive an e-mail at each step: taken up, question from support, resolution, closure.'],
                ],
            ],
            [
                'id' => 'statuts',
                'keywords' => ['status', 'state', 'meaning', 'new', 'in progress', 'closed', 'rejected'],
                'q'  => 'What do the statuses mean?',
                'a'  => [
                    ['list', [
                        'New: the ticket is recorded, no technician has taken it yet.',
                        'In progress: a technician is working on it.',
                        'On hold: support is waiting for your reply.',
                        'Resolved: support has fixed the problem and is waiting for your confirmation.',
                        'Closed: the ticket is finished.',
                        'Rejected: support will not act on it; the reason is in the ticket’s messages.',
                    ]],
                ],
            ],
            [
                'id' => 'en-attente',
                'keywords' => ['on hold', 'reply', 'answer', 'question from support', 'waiting for me'],
                'q'  => 'My ticket is “On hold”: what should I do?',
                'a'  => [
                    ['p', 'Support has asked you a question: it is in the ticket’s messages and you received it by e-mail. Reply in the “Add a message” field: the ticket automatically goes back to “In progress”.'],
                    ['note', 'Until you reply, the repair cannot move forward. A banner at the top of “My tickets” lists the tickets waiting for an action from you.'],
                ],
            ],
            [
                'id' => 'resolu',
                'keywords' => ['resolved', 'confirm', 'still broken', 'not fixed'],
                'q'  => 'My ticket is “Resolved”: what should I do?',
                'a'  => [
                    ['list', [
                        'The problem is fixed: click “Yes, close the ticket”.',
                        'The problem persists: click “No, the problem persists” and explain what is still not working. The ticket goes back to the technician.',
                    ]],
                    ['p', 'Without a reply from you, the ticket is closed automatically 7 days after it was resolved.'],
                ],
            ],
            [
                'id' => 'completer',
                'keywords' => ['add', 'complete', 'photo', 'attachment', 'more information'],
                'q'  => 'How do I add information after reporting?',
                'a'  => [
                    ['p', 'Open the ticket and write in “Add a message”. The technician in charge is notified by e-mail.'],
                    ['note', 'An attachment can only be added when reporting. If you later need to share a photo, describe what it shows in a message.'],
                ],
            ],
            [
                'id' => 'probleme-revient',
                'keywords' => ['back again', 'again', 'recurring', 'closed ticket', 'reopen'],
                'q'  => 'The problem is back but the ticket is closed',
                'a'  => [
                    ['p', 'A closed or rejected ticket can no longer be changed. Report a new incident and give the old ticket’s reference in the description: support will link them, and the failure will count as recurring in the statistics.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'bons-reflexes',
        'title' => 'Before reporting: good habits',
        'items' => [
            [
                'id' => 'danger',
                'keywords' => ['smoke', 'fire', 'burning', 'sparks', 'electric shock', 'water leak', 'hazard', 'danger'],
                'q'  => 'There is a hazard: smoke, burning smell, sparks, water',
                'a'  => [
                    ['warn', 'Do not touch the device, move the students away and alert the school life office or the administration immediately. Do not attempt any repair. The report on the portal comes afterwards, to keep a record.'],
                ],
            ],
            [
                'id' => 'verifications',
                'keywords' => ['check', 'tips', 'advice', 'restart', 'screenshot', 'before reporting'],
                'q'  => 'What should I check before reporting a failure?',
                'a'  => [
                    ['p', 'These three steps fix many small failures:'],
                    ['steps', [
                        'Restart the device, after saving your work.',
                        'Check that the cables are firmly plugged in at both ends and that the power strip is switched on.',
                        'Try another computer or another socket, to find out whether the problem comes from the device or from the room.',
                    ]],
                    ['p', 'Also write down the exact error message. To take a screenshot in Windows, press [[Windows]] + [[Shift]] + [[S]], or simply photograph the screen with your phone.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'ordinateurs',
        'title' => 'Computers and peripherals',
        'items' => [
            [
                'id'     => 'pc-ne-demarre-pas',
                'keywords' => ['does not turn on', 'will not start', 'dead', 'power', 'tower', 'power button'],
                'q'      => 'The computer does not turn on',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check that the power strip is switched on (its switch is often red) and plugged into the wall.',
                        'Check that the power cable is pushed fully into the back of the tower.',
                        'Press the power button once, firmly, then wait 30 seconds.',
                        'Try another wall socket.',
                    ]],
                ],
            ],
            [
                'id'     => 'ecran-noir',
                'keywords' => ['black screen', 'no signal', 'monitor', 'screen off', 'display'],
                'q'      => 'The computer is running but the screen stays black',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check that the monitor is on: it has its own button and its own light.',
                        'Move the mouse or press a key: the computer may be asleep.',
                        'Check the cable between the monitor and the tower (HDMI, DisplayPort or VGA), at both ends.',
                        'If the monitor shows “No signal”, use its “Source” or “Input” button to select the right input.',
                    ]],
                ],
            ],
            [
                'id'     => 'clavier-souris',
                'keywords' => ['keyboard', 'mouse', 'keys', 'cursor', 'usb', 'wireless'],
                'q'      => 'The keyboard or mouse no longer responds',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Unplug and plug the USB cable back in, if possible into another port.',
                        'For a wireless model, check its switch and replace the batteries.',
                        'Restart the computer.',
                    ]],
                    ['note', 'To restart without a mouse: press [[Ctrl]] + [[Alt]] + [[Del]], then reach the power button with the [[Tab]] key and the arrow keys.'],
                ],
            ],
            [
                'id'     => 'lent-ou-fige',
                'keywords' => ['slow', 'frozen', 'freeze', 'stuck', 'not responding', 'update'],
                'q'      => 'The computer is very slow or frozen',
                'ticket' => [1, 'medium'],
                'a'      => [
                    ['steps', [
                        'Wait a minute: an update or a backup may be slowing the computer down.',
                        'Close the programs and tabs you do not need.',
                        'If a program no longer responds, press [[Ctrl]] + [[Shift]] + [[Esc]] to open the Task Manager, select the program, then click “End task”.',
                        'As a last resort, restart the computer.',
                    ]],
                    ['warn', 'If the screen says an update is in progress, do not switch the computer off: you could make it unusable.'],
                ],
            ],
            [
                'id'     => 'imprimante',
                'keywords' => ['print', 'printing', 'paper jam', 'paper', 'toner', 'ink', 'cartridge', 'copier'],
                'q'      => 'The printer does not print',
                'ticket' => [1, 'low'],
                'a'      => [
                    ['steps', [
                        'Check that it is on, with no error light, and that it has paper.',
                        'For a paper jam, follow the instructions on its screen to open the right cover, and pull the sheet out gently without tearing it.',
                        'On the computer, check that you are printing to the right printer.',
                        'Send the print job again only once: repeated jobs clog the print queue.',
                    ]],
                    ['note', 'Empty toner or cartridge: report a Low priority incident, giving the printer model and the room.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'reseau',
        'title' => 'Network, Wi-Fi and Internet',
        'items' => [
            [
                'id'     => 'pas-internet',
                'keywords' => ['internet', 'network', 'network cable', 'ethernet', 'wired', 'offline'],
                'q'      => 'No Internet on a desktop computer',
                'ticket' => [2, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check that the network cable is clipped into the computer and the wall socket: you should hear a click.',
                        'Check whether the other computers in the room have Internet.',
                        'If only one computer is affected, restart it.',
                    ]],
                    ['p', 'If the whole room has no Internet, report a High priority incident.'],
                ],
            ],
            [
                'id'     => 'wifi',
                'keywords' => ['wifi', 'wireless', 'access point', 'airplane mode', 'tablet', 'phone'],
                'q'      => 'I cannot connect to the Wi-Fi',
                'ticket' => [2, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check that Wi-Fi is on and that airplane mode is off.',
                        'Check that you are choosing the school’s network.',
                        'Remove this network from your saved networks (“Forget this network”), then reconnect with your credentials.',
                        'Move closer to the Wi-Fi access point, or try from another room.',
                    ]],
                    ['p', 'If nobody can connect in that area, report an incident giving the exact location.'],
                ],
            ],
            [
                'id'     => 'ent-ou-site',
                'keywords' => ['ent', 'website', 'site', 'pronote', 'browser', 'web page', 'unreachable'],
                'q'      => 'The ENT or a website does not open',
                'ticket' => [2, 'high'],
                'a'      => [
                    ['steps', [
                        'Open another well-known website: if it loads, the Internet works and the problem comes from the site itself.',
                        'Reload the page, or open it in a private window: [[Ctrl]] + [[Shift]] + [[N]] in Chrome and Edge.',
                        'For the ENT, check your credentials: they are your ENT ones, not those of this portal.',
                    ]],
                    ['note', 'If an online service is down for everyone, support can often only wait until it is restored. Report it anyway if it blocks your lessons: support will know the school is affected.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'logiciels',
        'title' => 'Software and files',
        'items' => [
            [
                'id'     => 'logiciel-plante',
                'keywords' => ['software', 'program', 'application', 'crash', 'closes', 'will not open', 'bug', 'error'],
                'q'      => 'A program does not start or closes on its own',
                'ticket' => [3, 'medium'],
                'a'      => [
                    ['steps', [
                        'Close the program completely, then start it again.',
                        'Restart the computer.',
                        'Try another computer: if the program works there, say so in your report.',
                    ]],
                    ['p', 'Write down the exact error message, or take a screenshot: it is the most useful information for the technician.'],
                ],
            ],
            [
                'id'     => 'licence',
                'keywords' => ['license', 'licence', 'expired', 'activation', 'license key', 'invalid'],
                'q'      => '“License expired” or “invalid license” message',
                'ticket' => [3, 'high'],
                'a'      => [
                    ['p', 'This is fixed on the license server: there is nothing you can do from the computer. Do not try to reinstall the program.'],
                    ['p', 'Report an incident and copy the exact message. If every computer in the room is affected and a lesson depends on it, choose High priority.'],
                ],
            ],
            [
                'id'     => 'fichier-perdu',
                'keywords' => ['file', 'document', 'lost', 'deleted', 'save', 'recover', 'recycle bin', 'word', 'excel'],
                'q'      => 'I lost a file or forgot to save it',
                'ticket' => [3, 'medium'],
                'a'      => [
                    ['steps', [
                        'Look in the program’s recent files: “File” menu, then “Open” or “Recent”.',
                        'Look in the Recycle Bin.',
                        'In Word, Excel or PowerPoint: “File”, “Info”, “Manage Document”, then “Recover Unsaved Documents”.',
                    ]],
                    ['note', 'On shared computers, save your documents to your personal storage space or a USB drive rather than the Desktop: the Desktop of a shared computer is not a safe place for your files.'],
                ],
            ],
            [
                'id'     => 'installer-logiciel',
                'keywords' => ['install', 'installation', 'download', 'new software'],
                'q'      => 'I need a program that is not installed',
                'ticket' => [3, 'low'],
                'a'      => [
                    ['p', 'Do not download software yourself: installations go through the digital support officers, who check the license and security.'],
                    ['p', 'Report a Low priority request, giving the program’s name, the rooms concerned and the date you need it by. Plan ahead: installing on a whole room takes time.'],
                ],
            ],
        ],
    ],

    [
        'id'    => 'audiovisuel',
        'title' => 'Projectors, sound and interactive whiteboards',
        'items' => [
            [
                'id'     => 'projecteur-sans-image',
                'keywords' => ['projector', 'beamer', 'no image', 'nothing displayed', 'remote control', 'hdmi', 'red light', 'lamp'],
                'q'      => 'The projector shows nothing',
                'ticket' => [4, 'high'],
                'a'      => [
                    ['steps', [
                        'Turn it on with the remote control or its button, then wait about a minute: the lamp needs to warm up.',
                        'Select the right input with the “Source” or “Input” button: HDMI 1, HDMI 2, VGA…',
                        'Check that the cable is plugged into the computer and the wall box.',
                        'On the computer, press [[Windows]] + [[P]] and choose “Duplicate”.',
                        'If the remote control does not respond, replace its batteries and aim at the projector’s sensor.',
                    ]],
                    ['warn', 'If a red light is blinking, do not switch it off and on several times in a row: it needs to cool down. Report the incident giving the colour and rhythm of the light.'],
                ],
            ],
            [
                'id'     => 'image-floue',
                'keywords' => ['blurry', 'blurred', 'focus', 'cut off', 'distorted', 'resolution'],
                'q'      => 'The image is blurred, cut off or distorted',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Adjust the sharpness with the focus ring on the lens, or from the remote control.',
                        'If the image is cut off or distorted, right-click the Desktop, choose “Display settings” and try a lower resolution.',
                    ]],
                ],
            ],
            [
                'id'     => 'pas-de-son',
                'keywords' => ['sound', 'audio', 'speakers', 'volume', 'mute', 'muted'],
                'q'      => 'No sound',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check the computer’s volume with the speaker icon, and that it is not muted.',
                        'Check that the speakers are on and that their own volume is turned up.',
                        'Click the speaker icon and choose the right audio output: speakers, projector or monitor.',
                        'Check the audio cable: green jack plug, or HDMI cable.',
                    ]],
                ],
            ],
            [
                'id'     => 'tableau-interactif',
                'keywords' => ['whiteboard', 'interactive', 'smartboard', 'pen', 'touch', 'calibration', 'frozen', 'stuck'],
                'q'      => 'The interactive whiteboard or pen no longer responds',
                'ticket' => [4, 'medium'],
                'a'      => [
                    ['steps', [
                        'Check the pen’s charge or batteries.',
                        'Check that the whiteboard’s USB cable is plugged into the computer.',
                        'Start the calibration (or “orientation”) tool of the whiteboard software and follow the steps.',
                        'Restart the computer with the whiteboard plugged in.',
                    ]],
                ],
            ],
        ],
    ],

    [
        'id'    => 'batiment',
        'title' => 'Building and facilities',
        'items' => [
            [
                'id'     => 'eclairage',
                'keywords' => ['light', 'lights', 'lighting', 'flickers', 'bulb', 'ceiling light'],
                'q'      => 'A light does not turn on or flickers',
                'ticket' => [5, 'low'],
                'a'      => [
                    ['p', 'Check the other switches in the room. Do not take anything apart, and do not stand on a table or chair to reach a light fitting.'],
                    ['p', 'Report the incident giving the position of the light, for example “at the back, window side”.'],
                ],
            ],
            [
                'id'     => 'prise-electrique',
                'keywords' => ['power socket', 'socket', 'outlet', 'electricity', 'power strip', 'circuit breaker'],
                'q'      => 'A power socket does not work',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['steps', [
                        'Plug in another device to check that the socket is really at fault.',
                        'If you use a power strip, check its switch.',
                    ]],
                    ['warn', 'Never touch the electrical panel and never reset a circuit breaker yourself: alert the administration.'],
                ],
            ],
            [
                'id'     => 'chauffage-fenetre',
                'keywords' => ['heating', 'radiator', 'cold', 'hot', 'blind', 'window', 'door', 'lock'],
                'q'      => 'Heating, blind, window or door not working',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['p', 'Report an incident giving the equipment and the room. A window that no longer closes or a jammed door can be a safety issue: in that case choose High priority.'],
                ],
            ],
            [
                'id'     => 'mobilier',
                'keywords' => ['chair', 'table', 'desk', 'furniture', 'broken', 'locker', 'cupboard'],
                'q'      => 'Broken or dangerous furniture',
                'ticket' => [5, 'medium'],
                'a'      => [
                    ['p', 'Move the table or chair aside so nobody uses it, then report the incident with a photo.'],
                ],
            ],
        ],
    ],
];
