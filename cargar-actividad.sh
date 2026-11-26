#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
bash respaldar.sh
docker compose exec -T --user sail laravel.test php artisan optimize:clear
docker compose exec -T --user sail laravel.test php artisan migrate --force
docker compose exec -T --user sail laravel.test php artisan almacen:cargar-actividad "$@"
echo 'Abre http://localhost:8000/dashboard?agrupacion=mes&periodos=3'
