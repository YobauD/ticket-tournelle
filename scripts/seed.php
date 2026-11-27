<?php
/**
 * Jeu de données de démonstration : quelques salles et un compte par rôle, avec sa fonction.
 *
 * Usage : php scripts/seed.php ["MotDePasseProvisoire"]
 * Sans argument, un mot de passe aléatoire est généré et affiché.
 * Chaque compte devra changer ce mot de passe à sa première connexion.
 * À ne lancer que sur une base de test : remplacez ces comptes avant la mise en service.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Models\JobTitle;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    exit('Ce script se lance en ligne de commande.');
}

$password = $argv[1] ?? 'Tt-' . bin2hex(random_bytes(6)) . '-A1';
if ($error = Auth::passwordError($password, '')) {
    exit("Mot de passe refusé ($error) : 12 caractères minimum, avec minuscule, majuscule et chiffre.\n");
}

// --- Lieux d'exemple (à remplacer par les salles et bureaux réels du lycée) --
$locations = [
    'A101', 'A102', 'A203 (salle informatique)', 'B01 (atelier)', 'B12',
    'Bureau du proviseur', 'Secrétariat', 'Vie scolaire', 'CDI', 'Salle des professeurs',
];
foreach ($locations as $name) {
    Database::query('INSERT IGNORE INTO locations (name) VALUES (?)', [$name]);
}

// --- Comptes : [rôle, nom, prénom, e-mail, niveau, langue, fonction] ---------
// La fonction (libellé français) est indépendante du rôle : « Direction » reste un utilisateur.
$roles  = array_column(User::roles(), 'id', 'code');
$titles = array_column(JobTitle::active(), 'id', 'label_fr');
$accounts = [
    ['superadmin', 'Moreau',  'Claire', 'superadmin@latournelle.local', null, 'fr', 'Direction'],
    ['admin',      'Admin',   'Alice',  'admin@latournelle.local',      null, 'fr', 'Référent numérique'],
    ['technician', 'Martin',  'Karim',  'tech1@latournelle.local',      1,    'fr', 'Référent numérique'],
    ['technician', 'Leroy',   'Sophie', 'tech2@latournelle.local',      2,    'fr', 'Référent numérique'],
    ['user',       'Dupont',  'Marie',  'prof@latournelle.local',       null, 'fr', 'Professeur'],
    ['user',       'Smith',   'John',   'teacher@latournelle.local',    null, 'en', 'Professeur'],
    ['user',       'Lambert', 'Sophie', 'direction@latournelle.local',  null, 'fr', 'Direction'],
];

$hash = Auth::hash($password);
foreach ($accounts as [$role, $last, $first, $email, $level, $lang, $title]) {
    $username = strstr($email, '@', true);   // identifiant de connexion : prof, admin, tech1…
    if (User::findByUsername($username) !== null) {
        echo "Déjà présent : $username\n";
        continue;
    }
    User::create((int) $roles[$role], $username, $last, $first, $email, $hash, $level, $lang, $titles[$title] ?? null);
    echo "Créé : $username, $email ($role, $title)\n";
}

echo "\nMot de passe provisoire des comptes créés : $password\n";
