-- =====================================================================
--  Ticket'Tournelle — Migration : lieux sans bâtiment
--  À importer UNE seule fois dans une base créée avant ce changement
--  (phpMyAdmin > Importer). Si elle a déjà été faite, l'import s'arrête
--  sur la première instruction avec une erreur, sans rien modifier.
--
--  Aucun ticket n'est perdu ni déplacé : chaque lieu garde son identifiant.
--  « A101 » dans « Bâtiment A » devient simplement « A101 ». Si deux bâtiments
--  avaient une salle du même nom, le bâtiment est ajouté entre parenthèses
--  pour les distinguer : « 101 (Bâtiment B) ».
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

-- 1. Nouvelles colonnes
ALTER TABLE locations
    ADD COLUMN name        VARCHAR(80) NULL AFTER id,
    ADD COLUMN is_verified TINYINT(1)  NOT NULL DEFAULT 1 AFTER is_active,
    ADD COLUMN created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_verified;

-- 2. Nom du lieu = nom de la salle ; bâtiment ajouté seulement pour départager un doublon
UPDATE locations l
  JOIN (SELECT room FROM locations GROUP BY room HAVING COUNT(*) > 1) doublon ON doublon.room = l.room
   SET l.name = LEFT(CONCAT(l.room, ' (', l.building, ')'), 80);
UPDATE locations SET name = LEFT(room, 80) WHERE name IS NULL;

-- 3. Suppression des anciennes colonnes ; nom obligatoire et unique
ALTER TABLE locations
    DROP INDEX uq_locations,
    DROP COLUMN building,
    DROP COLUMN room,
    MODIFY name VARCHAR(80) NOT NULL,
    ADD UNIQUE KEY uq_locations_name (name);
