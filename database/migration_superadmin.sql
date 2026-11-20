-- =====================================================================
--  Ticket'Tournelle — Migration : rôle superadministrateur
--  À importer UNE fois dans une base créée avant l'ajout de ce rôle
--  (phpMyAdmin > Importer). Sans effet si le rôle existe déjà.
--  Ne modifie aucun compte, aucun ticket.
--
--  Ensuite, désignez le superadministrateur sur le serveur :
--      php scripts/superadmin.php <e-mail> [<Prénom> <Nom>]
--  Poste de développement MAMP : réimportez plutôt database/mamp_setup.sql,
--  qui ajoute le compte de démonstration superadmin@latournelle.local.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

INSERT IGNORE INTO roles (code, label_fr, label_en) VALUES
    ('superadmin', 'Superadministrateur', 'Super administrator');
