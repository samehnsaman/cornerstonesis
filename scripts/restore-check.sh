#!/bin/sh
set -eu
dump="${1:?usage: restore-check.sh /backups/database.dump}"
db="cornerstonesis_restore_check"
export PGPASSWORD="$POSTGRES_PASSWORD"
dropdb -U "$POSTGRES_USER" --if-exists "$db"
createdb -U "$POSTGRES_USER" "$db"
pg_restore -U "$POSTGRES_USER" -d "$db" --exit-on-error "$dump"
psql -U "$POSTGRES_USER" -d "$db" -c 'select count(*) as migrations from migrations;'
dropdb -U "$POSTGRES_USER" "$db"
