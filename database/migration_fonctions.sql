-- =====================================================================
--  Ticket'Tournelle — Migration : fonctions des personnels
--  À importer UNE seule fois dans une base créée avant ce changement
--  (phpMyAdmin > Importer). Si elle a déjà été faite, l'import s'arrête
--  sur l'ajout de la colonne avec une erreur, sans rien modifier.
--
--  Ajoute la liste des fonctions (Professeur, Direction, Vie scolaire…) et
--  une fonction facultative sur chaque compte. Aucun compte n'est modifié :
--  les fonctions s'attribuent ensuite dans Administration > Utilisateurs.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS job_titles (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    label_fr   VARCHAR(80)       NOT NULL,
    label_en   VARCHAR(80)       NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_titles_fr (label_fr),
    UNIQUE KEY uq_job_titles_en (label_en)
) ENGINE=InnoDB;

INSERT IGNORE INTO job_titles (label_fr, label_en) VALUES
    ('Professeur',                'Teacher'),
    ('Direction',                 'Senior management'),
    ('Vie scolaire',              'Student life'),
    ('Administration et gestion', 'Administrative staff'),
    ('Documentaliste',            'Librarian'),
    ('Agent technique',           'Maintenance staff'),
    ('Référent numérique',        'Digital support officer');

ALTER TABLE users
    ADD COLUMN job_title_id SMALLINT UNSIGNED NULL AFTER role_id,
    ADD CONSTRAINT fk_users_job_title FOREIGN KEY (job_title_id) REFERENCES job_titles (id) ON DELETE SET NULL;
