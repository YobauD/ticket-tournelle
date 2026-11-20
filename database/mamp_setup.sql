-- =====================================================================
--  Ticket'Tournelle — Données de développement pour MAMP
--  À importer APRÈS database.sql (phpMyAdmin > Importer).
--
--  1. Compte MySQL de l'application, au moindre privilège
--  2. Lieux d'exemple
--  3. Comptes de démonstration (dont superadmin@latournelle.local et
--     direction@latournelle.local, un compte Utilisateur de fonction Direction),
--     mot de passe provisoire : Provisoire-2026A
--     (à changer à la première connexion)
--
--  Réservé au poste de développement : ne pas importer sur le serveur du lycée.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  1. Compte applicatif : lire et écrire des lignes, rien d'autre
--     (ni DROP, ni ALTER, ni accès aux autres bases).
--     Deux hôtes, car selon la configuration de MySQL une connexion
--     locale est vue comme 'localhost' ou comme '127.0.0.1'.
--     Ce mot de passe est celui de config/config.local.php.
-- ---------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'tt_app'@'localhost' IDENTIFIED BY 'TicketTournelle-Local-2026';
CREATE USER IF NOT EXISTS 'tt_app'@'127.0.0.1' IDENTIFIED BY 'TicketTournelle-Local-2026';
ALTER USER 'tt_app'@'localhost' IDENTIFIED BY 'TicketTournelle-Local-2026';
ALTER USER 'tt_app'@'127.0.0.1' IDENTIFIED BY 'TicketTournelle-Local-2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON ticket_tournelle.* TO 'tt_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON ticket_tournelle.* TO 'tt_app'@'127.0.0.1';
FLUSH PRIVILEGES;

-- ---------------------------------------------------------------------
--  2. Lieux d'exemple (à remplacer par les salles et bureaux réels du lycée).
--     Les déclarants peuvent aussi saisir un lieu absent de cette liste.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO locations (name) VALUES
    ('A101'),
    ('A102'),
    ('A203 (salle informatique)'),
    ('B01 (atelier)'),
    ('B12'),
    ('Bureau du proviseur'),
    ('Secrétariat'),
    ('Vie scolaire'),
    ('CDI'),
    ('Salle des professeurs');

-- ---------------------------------------------------------------------
--  3. Comptes de démonstration, un par rôle (superadministrateur compris).
--     Le mot de passe est stocké haché (BCrypt), jamais en clair.
--     must_change_password = 1 : changement imposé à la première connexion.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO users (role_id, last_name, first_name, email, password_hash, tech_level, lang, must_change_password)
SELECT r.id, d.last_name, d.first_name, d.email,
       '$2y$10$WzYO63cAbvJXb973WhKSBOrzpEpyzj4sxhx3VfEBQT/1M2Crma8ai',
       d.tech_level, d.lang, 1
  FROM (
        SELECT 'superadmin' AS role, 'Moreau' AS last_name, 'Claire' AS first_name, 'superadmin@latournelle.local' AS email, NULL AS tech_level, 'fr' AS lang
        UNION ALL SELECT 'admin', 'Admin', 'Alice', 'admin@latournelle.local', NULL, 'fr'
        UNION ALL SELECT 'technician', 'Martin', 'Karim',  'tech1@latournelle.local',   1,    'fr'
        UNION ALL SELECT 'technician', 'Leroy',  'Sophie', 'tech2@latournelle.local',   2,    'fr'
        UNION ALL SELECT 'user',       'Dupont', 'Marie',  'prof@latournelle.local',    NULL, 'fr'
        UNION ALL SELECT 'user',       'Smith',  'John',   'teacher@latournelle.local', NULL, 'en'
        UNION ALL SELECT 'user',       'Lambert', 'Sophie', 'direction@latournelle.local', NULL, 'fr'
       ) d
  JOIN roles r ON r.code = d.role;

-- ---------------------------------------------------------------------
--  4. Fonction de chaque compte de démonstration (seulement si elle n'est pas
--     déjà renseignée : un réimport ne défait pas ce qu'un administrateur a choisi)
-- ---------------------------------------------------------------------
UPDATE users u
  JOIN (
        SELECT 'superadmin@latournelle.local' AS email, 'Direction' AS fonction
        UNION ALL SELECT 'admin@latournelle.local',     'Référent numérique'
        UNION ALL SELECT 'tech1@latournelle.local',     'Référent numérique'
        UNION ALL SELECT 'tech2@latournelle.local',     'Référent numérique'
        UNION ALL SELECT 'prof@latournelle.local',      'Professeur'
        UNION ALL SELECT 'teacher@latournelle.local',   'Professeur'
        UNION ALL SELECT 'direction@latournelle.local', 'Direction'
       ) d ON d.email = u.email
  JOIN job_titles j ON j.label_fr = d.fonction
   SET u.job_title_id = j.id
 WHERE u.job_title_id IS NULL;
