<?php
/**
 * Crée un compte superadministrateur, ou attribue ce rôle à un compte existant,
 * avec un mot de passe provisoire à changer à la première connexion.
 *
 * C'est le SEUL moyen de gérer un superadministrateur : le site ne le permet pas, pour
 * qu'un compte volé ne puisse pas en créer d'autres. Il faut donc un accès au serveur.
 * Relancer le script sur le même compte lui donne un nouveau mot de passe provisoire :
 * c'est la procédure de secours si le mot de passe du superadministrateur est perdu.
 *
 * Usage :
 *   php scripts/superadmin.php <e-mail>                    compte existant
 *   php scripts/superadmin.php <e-mail> <Prénom> <Nom>     nouveau compte
 *   … --fonction="Direction"                               fixe aussi sa fonction (libellé français)
 *
 * Sous MAMP pour Windows : C:\MAMP\bin\php\php8.x.x\php.exe scripts\superadmin.php …
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Models\Log;
use App\Models\PasswordRequest;
use App\Models\User;

if (PHP_SAPI !== 'cli') {
    exit('Ce script se lance en ligne de commande, sur le serveur.');
}

// Option --fonction="…" : retirée des arguments, qui restent dans l'ordre e-mail, prénom, nom
$fonction = null;
$args     = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--fonction=')) {
        $fonction = trim(substr($arg, strlen('--fonction=')));
    } else {
        $args[] = $arg;
    }
}

$jobTitleId = null;
if ($fonction !== null) {
    $jobTitleId = Database::query('SELECT id FROM job_titles WHERE label_fr = ? AND is_active = 1', [$fonction])->fetchColumn();
    if ($jobTitleId === false) {
        $list = implode(', ', Database::query('SELECT label_fr FROM job_titles WHERE is_active = 1 ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN));
        fwrite(STDERR, "Fonction inconnue : « $fonction ». Fonctions possibles : $list.\n");
        exit(1);
    }
    $jobTitleId = (int) $jobTitleId;
}

$email = mb_strtolower(trim($args[0] ?? ''));
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Usage : php scripts/superadmin.php <e-mail> [<Prénom> <Nom>] [--fonction=\"Direction\"]\n");
    exit(1);
}

$roleId = Database::query("SELECT id FROM roles WHERE code = 'superadmin'")->fetchColumn();
if ($roleId === false) {
    fwrite(STDERR, "Le rôle superadmin n'existe pas : importez d'abord database/migration_superadmin.sql.\n");
    exit(1);
}

// Mot de passe provisoire aléatoire, conforme à la politique (minuscule, majuscule, chiffre, 12+)
$password = 'Sa-' . bin2hex(random_bytes(6)) . '-' . random_int(10, 99) . 'Z';
$user     = User::findByEmail($email);

if ($user === null) {
    $firstName = trim($args[1] ?? '');
    $lastName  = trim($args[2] ?? '');
    if ($firstName === '' || $lastName === '') {
        fwrite(STDERR, "Aucun compte avec cette adresse : indiquez le prénom et le nom pour le créer.\n");
        exit(1);
    }
    $id = User::create((int) $roleId, $lastName, $firstName, $email, Auth::hash($password), null, 'fr', $jobTitleId);
    echo "Compte superadministrateur créé : $email\n";
} else {
    $id = (int) $user['id'];
    User::updateAccess($id, (int) $roleId, null, true);
    User::setPassword($id, Auth::hash($password), true);   // lève aussi un éventuel blocage
    echo "Compte $email : rôle superadministrateur, nouveau mot de passe provisoire.\n";
    if (PasswordRequest::closeFor($id, 'done', null)) {    // demande faite depuis la page de connexion
        echo "Sa demande « mot de passe oublié » est close.\n";
    }
    if ($jobTitleId !== null) {
        User::setJobTitle($id, $jobTitleId);
    }
}
if ($fonction !== null) {
    echo "Fonction : $fonction\n";
}

// Trace dans le journal d'audit : auteur vide = action faite sur le serveur
Log::write('superadmin_granted', 'user:' . $id, 'ligne de commande du serveur');

echo "Mot de passe provisoire : $password\n";
echo "Il devra être changé à la première connexion. Transmettez-le de vive voix, jamais par e-mail.\n";
