#!/bin/bash
# =====================================================================
#  Sauvegarde quotidienne : base de données + pièces jointes.
#
#  Identifiants MySQL : dans /root/.my.cnf (chmod 600), jamais dans ce script :
#      [client]
#      user=tt_backup
#      password=...
#  Planification (cron de root), tous les jours à 2 h :
#      0 2 * * * /var/www/ticket-tournelle/scripts/backup.sh
#
#  À compléter par une copie hors du serveur : une sauvegarde stockée
#  sur la machine qu'elle protège ne couvre pas la panne de cette machine.
# =====================================================================
set -euo pipefail

APP_DIR="/var/www/ticket-tournelle"
BACKUP_DIR="/var/backups/ticket-tournelle"
KEEP_DAYS=14
STAMP=$(date +%Y-%m-%d_%H%M)

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

# --single-transaction : sauvegarde cohérente sans bloquer le site
mysqldump --single-transaction --routines ticket_tournelle | gzip > "$BACKUP_DIR/db_$STAMP.sql.gz"
tar -czf "$BACKUP_DIR/uploads_$STAMP.tar.gz" -C "$APP_DIR/storage" uploads

# Rotation : suppression des sauvegardes de plus de KEEP_DAYS jours
find "$BACKUP_DIR" -type f -mtime +"$KEEP_DAYS" -delete

echo "$(date '+%F %T') - sauvegarde $STAMP terminée"
