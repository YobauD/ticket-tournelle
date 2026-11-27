-- =====================================================================
--  Ticket'Tournelle — Migration : adresse e-mail choisie par la personne
--  À importer dans une base créée avant ce changement (phpMyAdmin > Importer).
--  Sans risque si elle est importée deux fois.
--
--  L'adresse e-mail n'est plus saisie par l'administrateur : chaque personne la
--  choisit et la confirme par un lien. La colonne devient facultative (vide tant
--  qu'aucune adresse n'est confirmée) et la table des liens envoyés est créée.
--  Les adresses déjà enregistrées sont conservées ; chacun peut la changer dans
--  « Mon compte ». Aucun mot de passe ni ticket n'est modifié.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

ALTER TABLE users MODIFY email VARCHAR(190) NULL;

-- reset  : lien « choisir un nouveau mot de passe », valable 1 heure, à usage unique
-- verify : lien de confirmation de l'adresse e-mail choisie par la personne ; l'adresse
--          n'est enregistrée sur le compte qu'une fois le lien ouvert
-- Seule l'empreinte SHA-256 du jeton est stockée : une fuite de la base ne donne aucun
-- lien utilisable. Un nouveau lien annule le précédent de même type.
CREATE TABLE IF NOT EXISTS email_tokens (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED  NOT NULL,
    purpose     ENUM('reset', 'verify') NOT NULL,
    email       VARCHAR(190)  NOT NULL,          -- adresse à laquelle le lien a été envoyé
    token_hash  CHAR(64)      NOT NULL,
    expires_at  DATETIME      NOT NULL,
    used_at     DATETIME      NULL,
    ip_address  VARCHAR(45)   NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_email_tokens_hash (token_hash),
    KEY idx_email_tokens_user (user_id, purpose, used_at),
    CONSTRAINT fk_email_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;
