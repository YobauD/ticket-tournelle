-- =====================================================================
--  Ticket'Tournelle : remise à zéro des mots de passe de démonstration
--
--  À importer dans phpMyAdmin (onglet Importer) quand on ne sait plus
--  le mot de passe d'un compte de démonstration. Ne touche ni aux tickets
--  ni aux autres comptes.
--
--  Les 7 comptes de démonstration (prof, admin…) reprennent le mot de passe provisoire
--  Provisoire-2026A, à changer à la première connexion. Leur blocage après
--  5 essais est levé et ils sont réactivés s'ils avaient été désactivés.
--
--  Réservé au poste de développement : ne jamais importer sur le serveur du
--  lycée, où un mot de passe se réinitialise depuis Administration > Utilisateurs
--  (ou avec scripts/superadmin.php pour le superadministrateur).
-- =====================================================================
USE ticket_tournelle;

UPDATE users
   SET password_hash        = '$2y$10$WzYO63cAbvJXb973WhKSBOrzpEpyzj4sxhx3VfEBQT/1M2Crma8ai',  -- Provisoire-2026A (BCrypt)
       must_change_password = 1,
       failed_attempts      = 0,
       locked_until         = NULL,
       is_active            = 1
 WHERE username IN ('superadmin', 'admin', 'tech1', 'tech2', 'prof', 'teacher', 'direction');

-- Comptes remis à zéro (doit afficher 7 lignes ; moins si un compte n'existe pas encore :
-- importer alors database/mamp_setup.sql, qui le crée)
SELECT username, must_change_password AS a_changer, is_active AS actif
  FROM users
 WHERE username IN ('superadmin', 'admin', 'tech1', 'tech2', 'prof', 'teacher', 'direction')
 ORDER BY id;
