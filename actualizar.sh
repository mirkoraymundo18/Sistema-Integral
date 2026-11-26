#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
compose=(docker compose)

echo 'Guardando respaldo de PostgreSQL...'
bash respaldar.sh

echo 'Actualizando configuración y tablas...'
"${compose[@]}" exec -T --user sail laravel.test php artisan optimize:clear
"${compose[@]}" exec -T --user sail laravel.test php artisan sistema:verificar-vistas
"${compose[@]}" exec -T --user sail laravel.test php artisan migrate --force

echo 'Compilando las pantallas...'
"${compose[@]}" exec -T --user sail laravel.test bash -lc 'npm run build || npm run build:portable'
"${compose[@]}" exec -T --user sail laravel.test php -r 'if (is_file("public/build/manifest.json") && is_file("public/hot")) { unlink("public/hot"); }'
"${compose[@]}" exec -T --user sail laravel.test php artisan optimize
echo 'Iniciando el vencimiento automático de reservas...'
app_container="$(docker compose ps -q laravel.test)"
vendor_type="$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/vendor"}}{{.Type}}{{end}}{{end}}' "$app_container")"
if [[ "$vendor_type" == volume ]]; then
    docker compose -f compose.yaml -f compose.performance.yaml up -d --no-deps --no-build scheduler
else
    docker compose up -d --no-deps --no-build scheduler
fi
echo 'Actualización terminada. Recarga http://localhost:8000'

