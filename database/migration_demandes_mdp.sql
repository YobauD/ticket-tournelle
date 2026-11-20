-- =====================================================================
--  Ticket'Tournelle — Migration : demandes de réinitialisation de mot de passe
--  À importer dans une base créée avant ce changement (phpMyAdmin > Importer).
--  Sans risque si elle est importée deux fois : la table n'est créée que si
--  elle n'existe pas. Aucun compte ni ticket n'est modifié.
-- =====================================================================

USE ticket_tournelle;
SET NAMES utf8mb4;

-- Faites depuis la page de connexion (« Mot de passe oublié ? ») et traitées par un
-- administrateur, qui remet un mot de passe provisoire après avoir vérifié l'identité.
-- Une seule demande « pending » par compte : l'application ne relance pas les administrateurs.
--   pending   : en attente        done      : mot de passe réinitialisé
--   dismissed : ignorée           cancelled : la personne s'est reconnectée entre-temps
CREATE TABLE IF NOT EXISTS password_requests (
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
