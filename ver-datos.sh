#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"

echo 'Iniciando el panel de datos. La primera vez Docker descargará Adminer...'
docker compose up -d pgsql adminer
printf '\nAbre en tu navegador: http://localhost:8081/?pgsql=pgsql\n'
printf 'Sistema: PostgreSQL\nServidor: pgsql\n'
docker compose exec -T pgsql sh -c 'printf "Usuario: %s\nBase de datos: %s\n" "$POSTGRES_USER" "$POSTGRES_DB"'
printf 'Contraseña: el valor de DB_PASSWORD en el archivo .env del proyecto.\n'
printf 'Después de entrar, pulsa Seleccionar datos en la tabla que quieras consultar.\n'
