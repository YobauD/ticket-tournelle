-- =====================================================================
--  Ticket'Tournelle — Script de création de la base de données
--  Cible : MySQL 8.0 / InnoDB / utf8mb4
--  Usage : mysql -u root -p < database/database.sql
--          ou phpMyAdmin > Importer (MAMP)
--  Compatible MySQL 5.7 et 8.0. Les contraintes CHECK sont vérifiées par MySQL 8.0.16+ ;
--  MySQL 5.7 les accepte mais les ignore. L'application contrôle de toute façon ces règles.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS ticket_tournelle
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ticket_tournelle;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS logs, password_requests, ticket_attachments, ticket_history, ticket_comments,
                     tickets, status, priorities, locations, categories, users, job_titles, roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  RÉFÉRENTIELS
-- ---------------------------------------------------------------------

-- Rôles applicatifs (RBAC). Le code est utilisé par le routeur PHP.
CREATE TABLE roles (
    id        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code      VARCHAR(20)      NOT NULL,
    label_fr  VARCHAR(50)      NOT NULL,
    label_en  VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB;

-- Fonctions des personnels (Professeur, Direction, Vie scolaire…), gérées par l'administrateur.
-- Indépendantes du rôle : le rôle fixe les droits sur le portail, la fonction dit ce que la
-- personne fait au lycée. Une proviseure et un professeur ont le même rôle « Utilisateur ».
CREATE TABLE job_titles (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    label_fr   VARCHAR(80)       NOT NULL,
    label_en   VARCHAR(80)       NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_job_titles_fr (label_fr),
    UNIQUE KEY uq_job_titles_en (label_en)
) ENGINE=InnoDB;

-- Catégories d'incidents (gérées par l'administrateur).
CREATE TABLE categories (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    label_fr   VARCHAR(80)       NOT NULL,
    label_en   VARCHAR(80)       NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Lieux : une salle (« A102 ») ou un bureau (« Bureau du proviseur »), sans notion de bâtiment.
-- Les déclarants saisissent le lieu librement ; un lieu inconnu est créé avec is_verified = 0
-- (« À vérifier ») et n'est proposé aux autres qu'une fois validé par un administrateur.
-- Unicité sur le nom : la collation ignore majuscules et accents (« a102 » = « A102 ») ;
-- les espaces et la ponctuation (« A 102 ») sont rapprochés par l'application (Location::key()).
CREATE TABLE locations (
    id           SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(80)       NOT NULL,
    is_active    TINYINT(1)        NOT NULL DEFAULT 1,
    is_verified  TINYINT(1)        NOT NULL DEFAULT 1,
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_name (name)
) ENGINE=InnoDB;

-- Priorités et délais SLA associés (en heures calendaires).
-- sla_response_h   : délai maximal de prise en charge
-- sla_resolution_h : délai maximal de résolution
CREATE TABLE priorities (
    id                TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    code              VARCHAR(20)       NOT NULL,
    label_fr          VARCHAR(50)       NOT NULL,
    label_en          VARCHAR(50)       NOT NULL,
    sla_response_h    SMALLINT UNSIGNED NOT NULL,
    sla_resolution_h  SMALLINT UNSIGNED NOT NULL,
    sort_order        TINYINT UNSIGNED  NOT NULL,   -- 1 = la moins urgente
    PRIMARY KEY (id),
    UNIQUE KEY uq_priorities_code (code)
) ENGINE=InnoDB;

-- Statuts du cycle de vie d'un ticket.
-- is_final = 1 : plus aucune transition possible (Clôturé, Rejeté).
CREATE TABLE status (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)      NOT NULL,
    label_fr    VARCHAR(50)      NOT NULL,
    label_en    VARCHAR(50)      NOT NULL,
    sort_order  TINYINT UNSIGNED NOT NULL,
    is_final    TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_status_code (code)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  UTILISATEURS
-- ---------------------------------------------------------------------

-- Comptes nominatifs (contrainte C1).
-- password_hash   : jamais de mot de passe en clair (Argon2id ou BCrypt)
-- job_title_id    : fonction au lycée (Professeur, Direction…), facultative
-- tech_level      : niveau de support 1/2/3, NULL si le compte n'est pas technicien
-- must_change_password : 1 après création ou réinitialisation par un administrateur
-- failed_attempts / locked_until : protection contre la force brute
CREATE TABLE users (
    id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role_id          TINYINT UNSIGNED NOT NULL,
    job_title_id     SMALLINT UNSIGNED NULL,
    last_name        VARCHAR(80)      NOT NULL,
    first_name       VARCHAR(80)      NOT NULL,
    email            VARCHAR(190)     NOT NULL,
    password_hash    VARCHAR(255)     NOT NULL,
    tech_level       TINYINT UNSIGNED NULL,
    lang             CHAR(2)          NOT NULL DEFAULT 'fr',
    must_change_password TINYINT(1)   NOT NULL DEFAULT 0,
    is_active        TINYINT(1)       NOT NULL DEFAULT 1,
    failed_attempts  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until     DATETIME         NULL,
    last_login_at    DATETIME         NULL,
    created_at       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id),
    CONSTRAINT fk_users_job_title FOREIGN KEY (job_title_id) REFERENCES job_titles (id) ON DELETE SET NULL,
    CHECK (tech_level IS NULL OR tech_level BETWEEN 1 AND 3),
    CHECK (lang IN ('fr', 'en'))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  TICKETS
-- ---------------------------------------------------------------------

-- reference     : identifiant lisible (TT-2026-00042), généré après insertion
-- technician_id : technicien en charge, NULL tant que personne ne s'est affecté
-- support_level : niveau de support actuellement responsable (escalade N1 -> N2 -> N3)
-- taken_at / resolved_at / closed_at : horodatages utilisés pour les KPI
CREATE TABLE tickets (
    id             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    reference      VARCHAR(20)       NULL,
    user_id        INT UNSIGNED      NOT NULL,
    technician_id  INT UNSIGNED      NULL,
    support_level  TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    category_id    SMALLINT UNSIGNED NOT NULL,
    location_id    SMALLINT UNSIGNED NOT NULL,
    priority_id    TINYINT UNSIGNED  NOT NULL,
    status_id      TINYINT UNSIGNED  NOT NULL,
    title          VARCHAR(150)      NOT NULL,
    description    TEXT              NOT NULL,
    created_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    taken_at       DATETIME          NULL,
    resolved_at    DATETIME          NULL,
    closed_at      DATETIME          NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tickets_reference (reference),
    KEY idx_tickets_user (user_id),
    KEY idx_tickets_queue (status_id, priority_id, created_at),
    KEY idx_tickets_recurrence (location_id, category_id, created_at),
    CONSTRAINT fk_tickets_user       FOREIGN KEY (user_id)       REFERENCES users (id),
    CONSTRAINT fk_tickets_technician FOREIGN KEY (technician_id) REFERENCES users (id),
    CONSTRAINT fk_tickets_category   FOREIGN KEY (category_id)   REFERENCES categories (id),
    CONSTRAINT fk_tickets_location   FOREIGN KEY (location_id)   REFERENCES locations (id),
    CONSTRAINT fk_tickets_priority   FOREIGN KEY (priority_id)   REFERENCES priorities (id),
    CONSTRAINT fk_tickets_status     FOREIGN KEY (status_id)     REFERENCES status (id),
    CHECK (support_level BETWEEN 1 AND 3)
) ENGINE=InnoDB;

-- Échanges sur un ticket. is_internal = 1 : note visible des techniciens uniquement.
CREATE TABLE ticket_comments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id    INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    body         TEXT         NOT NULL,
    is_internal  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_ticket (ticket_id, created_at),
    CONSTRAINT fk_comments_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB;

-- Historique : une ligne par événement (création, changement de statut,
-- affectation, escalade, clôture automatique). user_id NULL = action du système.
CREATE TABLE ticket_history (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    ticket_id      INT UNSIGNED     NOT NULL,
    user_id        INT UNSIGNED     NULL,
    event          VARCHAR(20)      NOT NULL,
    old_status_id  TINYINT UNSIGNED NULL,
    new_status_id  TINYINT UNSIGNED NULL,
    note           VARCHAR(255)     NULL,
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_history_ticket (ticket_id, created_at),
    CONSTRAINT fk_history_ticket FOREIGN KEY (ticket_id)     REFERENCES tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user   FOREIGN KEY (user_id)       REFERENCES users (id),
    CONSTRAINT fk_history_old    FOREIGN KEY (old_status_id) REFERENCES status (id),
    CONSTRAINT fk_history_new    FOREIGN KEY (new_status_id) REFERENCES status (id)
) ENGINE=InnoDB;

-- Pièces jointes. Le fichier est stocké hors de la racine web sous un nom aléatoire
-- (stored_name) ; original_name ne sert qu'à l'affichage et au téléchargement.
CREATE TABLE ticket_attachments (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id      INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    original_name  VARCHAR(190) NOT NULL,
    stored_name    VARCHAR(80)  NOT NULL,
    mime_type      VARCHAR(100) NOT NULL,
    size_bytes     INT UNSIGNED NOT NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attachments_stored (stored_name),
    KEY idx_attachments_ticket (ticket_id),
    CONSTRAINT fk_attachments_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    CONSTRAINT fk_attachments_user   FOREIGN KEY (user_id)   REFERENCES users (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  DEMANDES DE RÉINITIALISATION DE MOT DE PASSE
-- ---------------------------------------------------------------------

-- Faites depuis la page de connexion (« Mot de passe oublié ? ») et traitées par un
-- administrateur, qui remet un mot de passe provisoire après avoir vérifié l'identité.
-- Une seule demande « pending » par compte : l'application ne relance pas les administrateurs.
--   pending   : en attente        done      : mot de passe réinitialisé
--   dismissed : ignorée           cancelled : la personne s'est reconnectée entre-temps
CREATE TABLE password_requests (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED  NOT NULL,
    message     VARCHAR(300)  NULL,          -- facultatif : où joindre la personne
    status      ENUM('pending', 'done', 'dismissed', 'cancelled') NOT NULL DEFAULT 'pending',
    ip_address  VARCHAR(45)   NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    handled_by  INT UNSIGNED  NULL,          -- administrateur qui l'a traitée ou ignorée
    handled_at  DATETIME      NULL,
    PRIMARY KEY (id),
    KEY idx_pwreq_user_status (user_id, status),
    CONSTRAINT fk_pwreq_user    FOREIGN KEY (user_id)    REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_pwreq_handler FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  JOURNAL D'AUDIT (contrainte C5)
-- ---------------------------------------------------------------------

-- Trace des actions sensibles : connexions, échecs, gestion des comptes et des rôles.
-- user_id NULL = action anonyme (échec de connexion) ou système.
CREATE TABLE logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NULL,
    action      VARCHAR(50)     NOT NULL,
    target      VARCHAR(100)    NULL,
    details     VARCHAR(500)    NULL,
    ip_address  VARCHAR(45)     NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_date (created_at),
    KEY idx_logs_action (action),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  DONNÉES DE RÉFÉRENCE
-- ---------------------------------------------------------------------

INSERT INTO roles (code, label_fr, label_en) VALUES
    ('user',       'Utilisateur',    'User'),
    ('technician', 'Technicien',     'Technician'),
    ('admin',      'Administrateur', 'Administrator'),
    ('superadmin', 'Superadministrateur', 'Super administrator');

INSERT INTO status (code, label_fr, label_en, sort_order, is_final) VALUES
    ('new',         'Nouveau',    'New',         1, 0),
    ('in_progress', 'En cours',   'In progress', 2, 0),
    ('pending',     'En attente', 'On hold',     3, 0),
    ('resolved',    'Résolu',     'Resolved',    4, 0),
    ('closed',      'Clôturé',    'Closed',      5, 1),
    ('rejected',    'Rejeté',     'Rejected',    6, 1);

-- Délais de résolution issus du cahier des charges (§ 2.1.3).
-- Les délais de prise en charge Basse et Moyenne sont à faire confirmer par la MOA.
INSERT INTO priorities (code, label_fr, label_en, sla_response_h, sla_resolution_h, sort_order) VALUES
    ('low',      'Basse',    'Low',      24, 72, 1),
    ('medium',   'Moyenne',  'Medium',    8, 48, 2),
    ('high',     'Haute',    'High',      4, 12, 3),
    ('critical', 'Critique', 'Critical',  1,  4, 4);

INSERT INTO job_titles (label_fr, label_en) VALUES
    ('Professeur',                'Teacher'),
    ('Direction',                 'Senior management'),
    ('Vie scolaire',              'Student life'),
    ('Administration et gestion', 'Administrative staff'),
    ('Documentaliste',            'Librarian'),
    ('Agent technique',           'Maintenance staff'),
    ('Référent numérique',        'Digital support officer');

INSERT INTO categories (label_fr, label_en) VALUES
    ('Matériel informatique',       'Computer hardware'),
    ('Réseau & Connectivité',       'Network & Connectivity'),
    ('Logiciels & Applications',    'Software & Applications'),
    ('Audiovisuel & Vidéoprojecteurs', 'Audiovisual & Projectors'),
    ('Bâtiment & Infrastructures',  'Building & Facilities');
