-- =====================================================================
--  Ticket'Tournelle — Migration : connexion par identifiant
--  À importer UNE seule fois dans une base créée avant ce changement
--  (phpMyAdmin > Importer). Si elle a déjà été faite, l'import s'arrête
--  sur l'ajout de la colonne avec une erreur, sans rien modifier.
--
--  On se connecte désormais avec un identifiant ; l'adresse e-mail (celle
--  de l'ENT) ne sert plus qu'aux notifications. Chaque compte existant
--  reçoit comme identifiant la partie de son adresse avant le @ :
--  prof@latournelle.local -> prof, marie.dupont@ac-versailles.fr -> marie.dupont.
--  Aucun mot de passe, ticket ni autre donnée n'est modifié.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER job_title_id;

UPDATE users SET username = LEFT(LOWER(SUBSTRING_INDEX(email, '@', 1)), 50);

-- Deux adresses identiques avant le @ (domaines différents) : le numéro du compte
-- est ajouté pour que chaque identifiant reste unique (dupont.12, dupont.31)
UPDATE users u
  JOIN (SELECT username FROM users GROUP BY username HAVING COUNT(*) > 1) d ON d.username = u.username
   SET u.username = CONCAT(LEFT(u.username, 40), '.', u.id);

ALTER TABLE users
    MODIFY username VARCHAR(50) NOT NULL,
    ADD UNIQUE KEY uq_users_username (username);
