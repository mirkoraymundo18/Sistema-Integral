#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
mkdir -p storage/app/private/reports

echo 'Ejecutando pruebas con SQLite en memoria. PostgreSQL operativo no se utiliza.'
docker compose exec -T --user sail laravel.test php vendor/bin/phpunit \
    --configuration phpunit.xml --colors=never --testdox \
    --log-junit storage/app/private/reports/pruebas.xml "$@"
echo 'Pruebas terminadas. Informe: storage/app/private/reports/pruebas.xml'
