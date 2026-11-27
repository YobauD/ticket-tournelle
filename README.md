# Ticket'Tournelle

Portail de déclaration et de suivi d'incidents du Lycée des Métiers La Tournelle.
Projet d'Atelier de Professionnalisation, BTS SIO SLAM 2ᵉ année.

PHP 8.2 (MVC sans framework), MySQL 8.0 via PDO, HTML/CSS/JavaScript sans bibliothèque.
Aucune dépendance externe : pas de Composer, pas de CDN.

## Arborescence

```
ticket-tournelle/
├── public/                 Seul dossier servi par Apache
│   ├── index.php           Contrôleur frontal (point d'entrée unique)
│   ├── .htaccess           Réécriture d'URL
│   └── assets/             css/app.css, js/app.js, img/logo.svg
├── app/
│   ├── bootstrap.php       Autoload, configuration, erreurs, en-têtes de sécurité, session
│   ├── helpers.php         e(), t(), url(), label(), csrf_field()...
│   ├── Core/               Config, Database, Router, Controller, View, Pagination,
│   │                       Session, Auth, Csrf, Lang, Mailer
│   ├── Controllers/        Auth, Account, Lang, Ticket, Technician, Admin, Help,
│   │                       Reference (catégories, lieux, fonctions), Audit, Stats
│   ├── Models/             User, Ticket, Comment, History, Attachment, Stats,
│   │                       Category, Location, JobTitle, PasswordRequest,
│   │                       Priority, Status, Log
│   ├── Services/           Notifier (e-mails bilingues), FaqBot (assistant d'aide),
│   │                       AccountPolicy (qui peut gérer quel compte)
│   ├── Views/              layouts/, partials/, auth/, tickets/, technician/,
│   │                       admin/, stats/, help/, emails/, errors/
│   └── Lang/               fr.php, en.php, help_fr.php, help_en.php (contenu de l'aide)
├── .htaccess               Redirige vers public/ quand le projet est dans un sous-dossier (MAMP)
├── config/                 config.php, config.local.php (MAMP), config.local.example.php, routes.php
├── database/               database.sql, mamp_setup.sql (compte MySQL + données de démonstration),
│                           migration_lieux.sql (lieux sans bâtiment, pour une base existante),
│                           migration_fonctions.sql (fonctions des personnels, pour une base existante),
│                           migration_demandes_mdp.sql (demandes « mot de passe oublié », pour une base existante),
│                           migration_identifiants.sql (connexion par identifiant, pour une base existante),
│                           migration_superadmin.sql (ajout du rôle à une base existante),
│                           reset_demo_passwords.sql (remet Provisoire-2026A aux comptes de démonstration)
├── scripts/                seed.php, auto_close.php, backup.sh, test_assistant.php,
│                           superadmin.php (création et secours du superadministrateur)
└── storage/                uploads/ (pièces jointes), logs/ — hors racine web
```

## Installation sous MAMP (poste de développement)

Testé dans une configuration équivalente : Apache avec le projet dans un sous-dossier
de `htdocs`, PHP 8.3, MySQL 8.0.

1. Copier le dossier `ticket-tournelle` dans `htdocs` de MAMP
   (macOS : `/Applications/MAMP/htdocs`, Windows : `C:\MAMP\htdocs`).
2. Dans MAMP, choisir PHP 8.1 ou plus récent, puis démarrer les serveurs.
3. Dans phpMyAdmin, onglet **Importer** : `database/database.sql`, puis `database/mamp_setup.sql`.
4. Le `config/config.local.php` fourni est réglé pour **MAMP sous Windows** (MySQL sur le port 3306).
   **Sur macOS**, y mettre `'port' => 8889` et `'full_url' => 'http://localhost:8888'`.
5. Ouvrir `http://localhost:8888/ticket-tournelle/` (Windows : `http://localhost/ticket-tournelle/`).

Comptes de démonstration (mot de passe provisoire `Provisoire-2026A`, à changer à la
première connexion) : voir le tableau de l'étape 5 ci-dessous.

Mot de passe d'un compte de démonstration oublié ? Importer `database/reset_demo_passwords.sql` :
les sept comptes reprennent `Provisoire-2026A` (à changer à la connexion suivante), sont
débloqués et réactivés. Réimporter `mamp_setup.sql` ne suffit pas : il ne modifie pas un compte
qui existe déjà.

Le fichier `.htaccess` à la racine du projet renvoie toutes les adresses vers `public/` :
le code, la configuration et les pièces jointes restent inaccessibles depuis le navigateur.
`database/mamp_setup.sql`, `database/reset_demo_passwords.sql` et le `config/config.local.php`
fourni sont réservés au poste de développement.

## Installation sur Ubuntu Server 22.04

### 1. Paquets

```bash
sudo apt update
sudo apt install apache2 mysql-server php8.2 libapache2-mod-php8.2 php8.2-mysql php8.2-mbstring
sudo a2enmod rewrite
```

Ubuntu 22.04 fournit PHP 8.1 par défaut, suffisant pour l'application ; PHP 8.2 s'installe
depuis le dépôt `ppa:ondrej/php` si le cahier des charges l'exige.

### 2. Base de données

```bash
sudo mysql < database/database.sql
sudo mysql
```

```sql
-- Compte applicatif au moindre privilège : ni DROP, ni ALTER, ni accès aux autres bases
CREATE USER 'tt_app'@'localhost' IDENTIFIED BY 'UnMotDePasseFort';
GRANT SELECT, INSERT, UPDATE, DELETE ON ticket_tournelle.* TO 'tt_app'@'localhost';
```

### 3. Code et configuration

```bash
sudo cp -r ticket-tournelle /var/www/
cd /var/www/ticket-tournelle
sudo cp config/config.local.example.php config/config.local.php   # remplace la configuration MAMP
sudo nano config/config.local.php            # mot de passe MySQL, URL du site, debug => false
sudo chown -R root:www-data .
sudo chmod -R o-rwx .
sudo chmod -R g+w storage                    # Apache n'écrit que dans storage/
```

### 4. Apache

`/etc/apache2/sites-available/ticket-tournelle.conf` :

```apache
<VirtualHost *:80>
    ServerName tickets.latournelle.local
    DocumentRoot /var/www/ticket-tournelle/public

    <Directory /var/www/ticket-tournelle/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite ticket-tournelle && sudo a2dissite 000-default && sudo systemctl reload apache2
```

Dans `/etc/php/8.2/apache2/php.ini`, pour accepter les pièces jointes de 5 Mo :
`upload_max_filesize = 5M` et `post_max_size = 6M`.

### 5. Comptes de démonstration

```bash
sudo -u www-data php scripts/seed.php
```

Le script crée quelques lieux et les comptes de démonstration, puis affiche leur mot de passe provisoire
(à changer à la première connexion) :

| Identifiant | E-mail (notifications) | Rôle | Fonction |
|---|---|---|---|
| `superadmin` | superadmin@latournelle.local | Superadministrateur | Direction |
| `admin` | admin@latournelle.local | Administrateur | Référent numérique |
| `tech1` | tech1@latournelle.local | Technicien N1 | Référent numérique |
| `tech2` | tech2@latournelle.local | Technicien N2 | Référent numérique |
| `prof` | prof@latournelle.local | Utilisateur (français) | Professeur |
| `teacher` | teacher@latournelle.local | Utilisateur (anglais) | Professeur |
| `direction` | direction@latournelle.local | Utilisateur | Direction |

### 6. Tâches planifiées

```
# /etc/cron.d/ticket-tournelle
0 6 * * * www-data php /var/www/ticket-tournelle/scripts/auto_close.php >> /var/www/ticket-tournelle/storage/logs/cron.log 2>&1
0 2 * * * root /var/www/ticket-tournelle/scripts/backup.sh >> /var/log/ticket-tournelle-backup.log 2>&1
```

## Développement en local

```bash
cp config/config.local.example.php config/config.local.php
php -S localhost:8000 -t public
```

Avec `'mail' => ['driver' => 'log']`, les e-mails ne partent pas : ils sont écrits dans
`storage/logs/mail.log`.

## Rôles et droits

| Action | Utilisateur | Technicien | Administrateur | Superadministrateur |
|---|:-:|:-:|:-:|:-:|
| Déclarer un incident, suivre ses tickets | oui | oui | oui | oui |
| Confirmer ou contester la résolution de son ticket | oui | oui | oui | oui |
| Voir tous les tickets, file d'attente | non | oui | oui | oui |
| Prendre en charge, changer le statut, escalader | non | selon son niveau | oui | oui |
| Gérer les comptes des enseignants, personnels et techniciens | non | non | oui | oui |
| Créer, modifier, désactiver un administrateur ; réinitialiser son mot de passe | non | non | **non** | oui |
| Gérer un compte superadministrateur | non | non | non | non : sur le serveur |
| Gérer les catégories, les lieux (validation, fusion) et les fonctions | non | non | oui | oui |
| Consulter le journal d'audit | non | non | oui | oui |
| Tableau de bord statistique | non | non | oui | oui |

Un technicien de niveau N ne traite que les tickets de niveau de support inférieur ou égal à N.

Toutes les règles sur les comptes sont réunies dans `app/Services/AccountPolicy.php`. L'écran
n'affiche que ce que la personne connectée a le droit de faire, et le serveur refait le contrôle :
une requête fabriquée à la main est refusée et inscrite au journal (« Action refusée »).

### Identifiant et adresse e-mail

On se connecte avec un **identifiant** (`marie.dupont`) et un mot de passe. L'**adresse e-mail
ENT** du compte ne sert qu'aux notifications. Les deux sont uniques.

- **Création d'un compte** (Administration > Utilisateurs) : l'identifiant est facultatif ;
  laissé vide, il est formé du prénom et du nom, sans accents (`Hélène Le Gall` →
  `helene.le-gall`), suivi d'un numéro s'il est déjà pris (`marie.dupont2`). Format : 2 à 50
  caractères, minuscules, chiffres, point, tiret ou tiret bas.
- **Mot de passe oublié** : on saisit son identifiant, ou son adresse e-mail si on ne s'en
  souvient plus.
- Le mot de passe ne peut être ni l'identifiant ni l'adresse e-mail.
- Le jour où la connexion par l'ENT sera possible (contrainte C1), l'identifiant pourra être
  celui de l'ENT ; l'e-mail restera l'adresse de notification.

**Base créée avant ce changement** : importer une fois `database/migration_identifiants.sql`
(phpMyAdmin > Importer). Chaque compte reçoit comme identifiant la partie de son adresse avant
le @ (`prof@latournelle.local` → `prof`) ; aucun mot de passe ni ticket n'est modifié. Si deux
adresses ont la même partie avant le @, le numéro du compte est ajouté (`dupont.12`).

### Fonctions

Le **rôle** fixe ce qu'une personne peut faire sur le portail ; la **fonction** dit ce qu'elle
fait au lycée : Professeur, Direction, Vie scolaire, Administration et gestion, Documentaliste,
Agent technique, Référent numérique. Les deux sont indépendants : la proviseure a la fonction
« Direction » et le rôle Utilisateur, elle déclare et suit ses tickets comme tout le monde. La
fonction ne donne aucun droit ; le serveur ne regarde que le rôle.

La fonction s'affiche sous le nom dans la barre supérieure, dans « Mon compte », à côté du
déclarant sur un ticket et dans la file d'attente : le technicien sait à qui il a affaire.
Elle est facultative ; sans fonction, c'est le rôle qui s'affiche.

Dans **Administration > Fonctions**, l'administrateur ajoute une fonction (libellés français et
anglais, contrainte C2), la renomme ou la désactive. Une fonction n'est jamais supprimée :
désactivée, elle n'est plus proposée pour un nouveau compte, mais les comptes qui l'ont la
gardent. Il choisit la fonction de chaque compte dans **Administration > Utilisateurs**, avec les
mêmes règles que pour le rôle : un administrateur ne modifie pas un compte administrateur.
Chaque changement est inscrit au journal d'audit (« Professeur -> Vie scolaire »).

**Base créée avant ce changement** : importer une fois `database/migration_fonctions.sql`
(phpMyAdmin > Importer). Elle crée la liste des fonctions sans modifier les comptes ni les
tickets ; les comptes existants restent sans fonction jusqu'à ce qu'un administrateur la
choisisse. Sur le poste MAMP, réimporter ensuite `database/mamp_setup.sql` donne leur fonction
aux comptes de démonstration et ajoute le compte `direction`.

### Superadministrateur

Le superadministrateur a tous les droits d'un administrateur, et lui seul gère les
administrateurs. Un compte superadministrateur ne se gère jamais depuis le site : un compte
volé ne peut donc pas en créer d'autres. Il se crée, ou se récupère si son mot de passe est
perdu, avec un accès au serveur :

```bash
php scripts/superadmin.php sophie.lambert sophie.lambert@ac-versailles.fr Sophie Lambert   # crée le compte
php scripts/superadmin.php sophie.lambert                       # compte existant : rôle + nouveau mot de passe
php scripts/superadmin.php sophie.lambert --fonction="Direction"   # fixe aussi la fonction
```

Le script affiche un mot de passe provisoire, à changer à la première connexion, et inscrit
l'opération au journal d'audit. Sous MAMP pour Windows, le PHP à utiliser est
`C:\MAMP\bin\php\php8.x.x\php.exe` (le numéro dépend de la version installée).

**Base créée avant l'ajout de ce rôle** : importer une fois `database/migration_superadmin.sql`
(phpMyAdmin > Importer). Elle ajoute le rôle sans toucher aux comptes ni aux tickets. Sur le
poste MAMP, réimporter ensuite `database/mamp_setup.sql` ajoute le compte de démonstration
`superadmin` sans modifier les autres.

### Mot de passe oublié

Le lien **« Mot de passe oublié ? »** de la page de connexion ouvre un formulaire : l'identifiant
(ou l'adresse e-mail) du compte et, si on le souhaite, où joindre la personne (salle, poste téléphonique).
Le portail n'envoie **jamais** de mot de passe ni de lien de réinitialisation par e-mail :
la demande prévient les administrateurs, qui vérifient l'identité de la personne, de vive voix
ou par téléphone, puis lui remettent un mot de passe provisoire.

- **Qui est prévenu** : par e-mail, chaque administrateur qui a le droit de réinitialiser ce
  mot de passe (les administrateurs et le superadministrateur pour un enseignant ou un
  technicien, le superadministrateur seul pour un administrateur). Pour un superadministrateur,
  qui ne se gère pas depuis le site, tous les autres administrateurs sont prévenus : l'écran
  leur indique que la réinitialisation se fait sur le serveur (`scripts/superadmin.php`).
- **Où la traiter** : en tête d'**Administration > Utilisateurs**, avec une pastille sur le lien
  « Administration » et sur l'onglet. « Remettre un mot de passe » pose le mot de passe
  provisoire et clôt la demande ; « Ignorer » la clôt sans rien changer (demande faite par
  quelqu'un d'autre, par exemple).
- **Annulation automatique** : si la personne retrouve son mot de passe et se connecte, sa
  demande est annulée. Un administrateur ne lui impose donc pas un mot de passe dont elle n'a
  plus besoin.
- **Protections** : la réponse est identique que l'adresse corresponde à un compte ou non (la
  page ne permet pas de savoir qui a un compte) ; une seule demande en attente par compte, les
  administrateurs ne sont prévenus qu'une fois ; 5 demandes par heure au plus depuis un même
  poste (`security.reset_requests_per_hour`). Un compte désactivé ne déclenche aucun e-mail.
- **Journal d'audit** : chaque demande (adresse inconnue comprise), chaque demande ignorée et
  chaque réinitialisation « suite à une demande ».

**Base créée avant ce changement** : importer une fois `database/migration_demandes_mdp.sql`
(phpMyAdmin > Importer). Elle crée la table des demandes sans toucher au reste ; l'importer deux
fois est sans risque.

## Cycle de vie d'un ticket

```
Nouveau ──► En cours ◄──► En attente
   │           │  ▲
   ▼           ▼  │ (réouverture par le déclarant)
Rejeté       Résolu ──► Clôturé
```

- **Nouveau → En cours** : un technicien prend le ticket en charge.
- **En attente** : le technicien pose une question ; la réponse du déclarant remet le ticket « En cours ».
- **Résolu → Clôturé** : le déclarant confirme, ou le script `auto_close.php` clôture après 7 jours.
- **Résolu → En cours** : le déclarant indique que le problème persiste.
- Un message au déclarant est obligatoire pour « En attente », « Résolu » et « Rejeté ».
- Chaque étape envoie un e-mail dans la langue du destinataire.

Les transitions sont définies dans `Ticket::TRANSITIONS` et vérifiées côté serveur.

## Mesures de sécurité (contraintes C4 et C5)

| Risque | Mesure | Où |
|---|---|---|
| Injection SQL | Requêtes préparées PDO uniquement, préparation réelle côté MySQL | `Core/Database.php` |
| XSS | Échappement de toutes les sorties avec `e()` ; en-tête CSP sans script ni style en ligne | `helpers.php`, `bootstrap.php` |
| CSRF | Jeton sur tous les formulaires POST, vérifié par le routeur ; cookie SameSite | `Core/Csrf.php`, `Core/Router.php` |
| Vol de mots de passe | Hachage Argon2id (BCrypt en repli), re-hachage automatique | `Core/Auth.php` |
| Force brute | Verrouillage 15 min après 5 échecs ; message d'erreur identique pour tous les cas | `Core/Auth.php`, `Models/User.php` |
| Mot de passe oublié | Aucun mot de passe par e-mail : remise par un administrateur après vérification d'identité ; réponse identique pour une adresse inconnue ; 5 demandes par heure et par poste | `Controllers/AuthController.php`, `Models/PasswordRequest.php` |
| Vol de session | Cookie HttpOnly et SameSite, nouvel identifiant à la connexion, expiration après 30 min | `Core/Session.php` |
| Élévation de privilèges | Rôle relu en base à chaque requête, contrôle par route et par ticket | `Core/Router.php`, `Models/Ticket.php` |
| Fichier malveillant | Type vérifié sur le contenu, liste blanche, nom aléatoire, stockage hors racine web | `Models/Attachment.php` |
| Mot de passe provisoire | Changement imposé à la première connexion, 12 caractères minimum | `Core/Router.php`, `Core/Auth.php` |
| Comptes administrateurs (C5) | Séparation des privilèges : seul le superadministrateur gère les administrateurs ; superadministrateur géré uniquement sur le serveur ; personne ne modifie son propre rôle ; refus inscrits au journal | `Services/AccountPolicy.php`, `scripts/superadmin.php` |
| Actions non tracées | Journal d'audit : connexions, échecs, comptes, rôles, mots de passe, référentiels ; consultable, sans modification ni suppression possibles | `Models/Log.php`, `Controllers/AuditController.php` |
| Requête fabriquée | Saisies qui ne sont pas du texte UTF-8 valide ignorées ; numéro de page ramené dans les bornes | `Core/Controller.php`, `Core/Pagination.php` |
| Fuite d'informations | Erreurs techniques journalisées, jamais affichées (`debug => false`) | `bootstrap.php` |

Le site doit être servi en HTTPS avant sa mise en service : sans cela, mots de passe et
cookies de session circulent en clair sur le réseau du lycée. Le cookie de session passe
automatiquement en mode `Secure` dès que le site est en HTTPS.

## Ajouter une langue

1. Copier `app/Lang/fr.php` vers `app/Lang/xx.php` et traduire les valeurs.
2. Ajouter `'xx'` à `lang.supported` dans `config/config.php`, et la clé `lang.xx` dans chaque fichier de langue.
3. Ajouter les colonnes `label_xx` aux tables de référentiels, et autoriser `'xx'` dans la contrainte `chk_users_lang`.

## Page d'aide

La page `/help` (icône « ? » de la barre) regroupe le mode d'emploi du portail et les
solutions de base aux incidents mineurs. Elle est liée depuis le formulaire de déclaration.

L'aide et l'assistant « Besoin d'aide ? » sont **réservés aux personnes connectées** : ni la
page de connexion ni celle du mot de passe oublié ne les affichent, et `/help` comme
`/help/ask` renvoient vers la connexion sans session. Ils restent ouverts pendant le
changement de mot de passe imposé à la première connexion, dont la fiche « Première
connexion » explique les règles. Une personne qui ne peut plus se connecter passe par le
lien « Mot de passe oublié ? ».

Le contenu est dans `app/Lang/help_fr.php` et `app/Lang/help_en.php`, séparé du code :
un référent peut ajouter une question sans toucher à la page. Règles à respecter :

- garder la même structure et les mêmes `id` dans les deux fichiers (contrainte C2) ;
- `'ticket' => [4, 'high']` ajoute le lien « Le problème persiste ? », qui ouvre le formulaire
  avec la catégorie n° 4 et la priorité Haute déjà choisies ;
- `[[Ctrl]]` s'affiche comme une touche de clavier ; aucun HTML n'est interprété.

Chaque réponse a une adresse directe (`/help#ecran-noir`), pratique pour répondre à un
collègue par un simple lien.

## Assistant d'aide

La bulle « Besoin d'aide ? », en bas à droite de chaque page (connexion comprise), ouvre un
assistant qui répond à partir du contenu de la FAQ. Après chaque solution, il demande si le
problème est réglé ; sinon, il propose le formulaire de déclaration déjà prérempli avec la
catégorie et la priorité conseillées. Un message qui évoque un danger (fumée, feu,
étincelles, eau) affiche d'abord la consigne de sécurité.

**Tout se passe sur le serveur du lycée.** Il n'y a ni intelligence artificielle ni service
extérieur : aucune question ne quitte l'établissement (§ 2.3.1 du cahier des charges,
contrainte C3).

Fonctionnement (`app/Services/FaqBot.php`) :

1. **Normalisation** : minuscules, accents retirés, synonymes ramenés à un seul mot
   (« projo », « vidéoprojecteur » et « projector » donnent « videoprojecteur »).
2. **Indexation** : chaque mot d'une question de la FAQ reçoit un poids selon l'endroit où il
   apparaît (titre 3, mots-clés 2,5, titre de la section 1, texte de la réponse 0,6).
3. **Score** : somme des poids des mots de la demande, multipliés par leur rareté dans la FAQ,
   puis ajustée selon la part des mots de la demande retrouvés (couverture).
4. **Décision** : une réponse nette est affichée ; deux réponses proches donnent un choix ;
   un score trop faible donne « je n'ai pas trouvé » avec les sujets et la déclaration.

Le navigateur reçoit les réponses en JSON (`POST /help/ask`, protégé par le jeton CSRF) et
les affiche comme du texte, jamais comme du HTML. La conversation suit d'une page à l'autre
et disparaît à la déconnexion : sur un poste partagé, la personne suivante ne la voit pas.

**Améliorer l'assistant** : si une question courante n'est pas comprise, ajoutez les mots
employés dans `'keywords'` de la bonne question (`help_fr.php` et `help_en.php`), ou un
synonyme dans `FaqBot::SYNONYMS`. Ajoutez la question au banc d'essai, puis relancez-le :

```bash
php scripts/test_assistant.php
```

Résultat actuel : sur 80 questions réalistes, 77 reçoivent directement la bonne réponse ;
pour les 3 autres, réellement ambiguës (« comment se connecter » : au portail ou au Wi-Fi ?),
l'assistant propose de choisir, la bonne réponse en premier. Limite connue : les fautes
d'orthographe au milieu d'un mot (« imprimente ») ne sont pas corrigées.

## Lieux : salles et bureaux

Il n'y a pas de bâtiments. Le déclarant tape la salle ou le bureau (« A102 », « Bureau du
proviseur ») ; les lieux connus lui sont proposés pendant la saisie (`<datalist>` HTML, sans
JavaScript).

Pour que les statistiques restent justes, une saisie est rapprochée d'un lieu existant quand
elle n'en diffère que par les majuscules, les accents, les espaces, la ponctuation ou le mot
« salle » : « salle a102 », « A 102 » et « A102 » désignent le même lieu
(`Location::key()`). Un numéro de salle est enregistré en majuscules, sans le mot « salle ».

Un lieu inconnu est accepté : il est enregistré sur le ticket, marqué « À vérifier » et n'est
proposé aux autres déclarants qu'une fois validé. Dans **Administration > Lieux**, un
compteur signale les lieux à vérifier ; l'administrateur :

- les **valide**, en corrigeant le nom si besoin ;
- ou les **fusionne** avec un lieu existant (« Salle des profs » dans « Salle des
  professeurs ») : les tickets passent à ce lieu, puis le doublon est supprimé.

Un lieu désactivé n'est plus accepté dans les déclarations. Les saisies de lieux, validations
et fusions sont inscrites au journal d'audit.

**Base créée avant ce changement** : importer une fois `database/migration_lieux.sql`
(phpMyAdmin > Importer). Aucun ticket n'est déplacé : « A101 » du « Bâtiment A » devient
« A101 » ; si deux bâtiments avaient une salle du même nom, le bâtiment est ajouté entre
parenthèses pour les distinguer.

## Indicateurs du tableau de bord

| Indicateur | Calcul |
|---|---|
| Temps moyen de résolution | Moyenne de « résolu le − déclaré le » sur les tickets résolus pendant la période (médiane affichée aussi) |
| Prise en charge moyenne | Moyenne de « affecté le − déclaré le » |
| Délais respectés | Part des tickets résolus dans le délai de leur priorité (4 h, 12 h, 48 h, 72 h) |
| Taux de récurrence | Part des tickets pour lesquels un autre ticket existait déjà dans le même lieu et la même catégorie au cours des 30 jours précédents |
| Volume par catégorie | Nombre de tickets déclarés pendant la période, par catégorie |

Les durées sont en heures calendaires, nuits et week-ends compris.

## Pistes d'évolution

- Accès en lecture seule aux statistiques pour la Direction : un rôle dédié (une ligne à changer
  dans `config/routes.php`), pas une fonction, puisqu'une fonction ne donne aucun droit.
- Délais calculés en heures ouvrées, comme l'indique le cahier des charges.
- Double authentification pour les comptes administrateurs et techniciens.
- Saisie d'un ticket « pour le compte de » un autre personnel.
- Statistiques des questions posées à l'assistant sans réponse, pour enrichir la FAQ.
- Mémoriser les fusions de lieux, pour qu'une saisie déjà fusionnée (« Salle des profs ») soit
  reconnue directement la fois suivante.
