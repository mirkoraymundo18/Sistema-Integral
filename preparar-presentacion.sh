#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"

# actualizar.sh backs up the database before migrating and compiling the interface.
bash actualizar.sh
docker compose exec -T --user sail laravel.test php artisan sistema:cargar-ejemplos "$@"
echo 'Presentación preparada. Recarga la aplicación para ver los datos y los botones Volver.'
