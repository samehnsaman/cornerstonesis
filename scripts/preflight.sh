#!/bin/sh
set -eu
root_pct="$(df -P / | awk 'NR==2 {gsub(/%/,"",$5); print $5}')"
[ "$root_pct" -lt 85 ] || { echo "BLOCKED: root filesystem is ${root_pct}% used (must be below 85%)."; exit 1; }
docker network inspect proxy >/dev/null
docker compose config --quiet
docker compose config | grep -E '^\s+ports:' && { echo 'BLOCKED: published ports detected'; exit 1; } || true
getent hosts sis.cornerstonelabs.org | grep -q '150.136.6.91' || { echo 'BLOCKED: DNS does not resolve to 150.136.6.91'; exit 1; }
for path in runtime/postgres runtime/valkey runtime/storage runtime/backups secrets; do [ -d "$path" ] || { echo "Missing $path"; exit 1; }; done
for file in secrets/app.env secrets/postgres.env; do [ -f "$file" ] || { echo "Missing $file"; exit 1; }; done
echo 'Preflight passed.'
