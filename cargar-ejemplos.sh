#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
umask 077

mkdir -p storage/app/private/backups
backup="storage/app/private/backups/antes-carga-ejemplos-$(date +%Y%m%d-%H%M%S)-$$.sql"
echo 'Guardando respaldo antes de agregar los datos de presentación...'
docker compose exec -T pgsql sh -c 'exec pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB"' > "$backup.partial"
test -s "$backup.partial"
mv -- "$backup.partial" "$backup"
echo "Respaldo guardado: $backup"

docker compose exec -T --user sail laravel.test php artisan migrate --force
docker compose exec -T --user sail laravel.test php artisan sistema:cargar-ejemplos "$@"
echo 'Listo. Recarga Clientes, Proyectos y lotes, Productos y Kardex; también puedes actualizar Adminer.'
