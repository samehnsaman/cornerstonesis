#!/bin/sh
set -eu
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
umask 077
pg_dump -Fc -U "$POSTGRES_USER" "$POSTGRES_DB" -f "/backups/database-$stamp.dump"
tar -czf "/backups/documents-$stamp.tar.gz" -C /documents .
sha256sum "/backups/database-$stamp.dump" "/backups/documents-$stamp.tar.gz" > "/backups/checksums-$stamp.sha256"
find /backups -type f -mtime +7 -delete
