-- =====================================================================
--  Ticket'Tournelle — Migration : durées de conservation (RGPD)
--  À importer UNE seule fois dans une base créée avant ce changement
--  (phpMyAdmin > Importer). Si elle a déjà été faite, l'import s'arrête
--  sur l'ajout de la colonne avec une erreur, sans rien modifier.
--
--  - users.deactivated_at : date de désactivation du compte. Le script
--    scripts/purge.php efface le nom, l'identifiant et l'adresse e-mail
--    des comptes désactivés depuis plus de 12 mois (réglable).
--  Les comptes déjà désactivés reçoivent la date d'aujourd'hui : ils gardent
--  donc 12 mois complets avant d'être effacés. Rien n'est supprimé ici.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN deactivated_at DATETIME NULL AFTER is_active;
UPDATE users SET deactivated_at = NOW() WHERE is_active = 0;
