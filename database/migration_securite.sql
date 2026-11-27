-- =====================================================================
--  Ticket'Tournelle — Migration : renforcement de la sécurité de la connexion
--  À importer UNE seule fois dans une base créée avant ce changement
--  (phpMyAdmin > Importer). Si elle a déjà été faite, l'import s'arrête
--  sur l'ajout de la colonne avec une erreur, sans rien modifier.
--
--  - users.session_version : augmente à chaque changement de mot de passe ;
--    les sessions ouvertes avant le changement sont alors fermées.
--  - Index du journal par adresse IP : compte vite les échecs de connexion
--    d'un même poste.
--  Aucun compte, mot de passe ni ticket n'est modifié.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER locked_until;
ALTER TABLE logs  ADD INDEX idx_logs_ip (ip_address, created_at);
