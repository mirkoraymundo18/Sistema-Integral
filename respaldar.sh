#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
umask 077
mkdir -p storage/app/private/backups
backup="storage/app/private/backups/almacen-$(date +%Y%m%d-%H%M%S)-$$.sql"
docker compose exec -T pgsql sh -c 'exec pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB"' > "$backup.partial"
test -s "$backup.partial"
mv -- "$backup.partial" "$backup"
printf 'Respaldo guardado: %s\n' "$backup"
