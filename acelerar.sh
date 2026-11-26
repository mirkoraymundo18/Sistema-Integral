#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
compose=(docker compose -f compose.yaml -f compose.performance.yaml)

if [[ ! -f vendor/autoload.php ]]; then
    echo 'Faltan las dependencias locales en vendor. Completa primero la instalación del proyecto.' >&2
    exit 1
fi

docker info > /dev/null

echo 'Preparando las dependencias en el almacenamiento de Linux. La primera copia puede tardar.'
"${compose[@]}" run --rm --no-deps --user root \
    --volume "$PWD/vendor:/opt/vendor-source:ro" \
    --entrypoint /bin/bash laravel.test -lc '
        set -euo pipefail
        if [[ ! -f /var/www/html/vendor/.almacen-ready ]]; then
            cp -a /opt/vendor-source/. /var/www/html/vendor/
            chown -R "$WWWUSER:$(id -g sail)" /var/www/html/vendor
            touch /var/www/html/vendor/.almacen-ready
        fi
        test -f /var/www/html/vendor/autoload.php
    '

echo 'Iniciando la aplicación con las dependencias en Linux.'
"${compose[@]}" up -d --no-build
"${compose[@]}" exec -T --user sail laravel.test php artisan optimize

echo 'Aplicación preparada. Abre http://localhost:8000 y prueba cambiar de pantalla.'
