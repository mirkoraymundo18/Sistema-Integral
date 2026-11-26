#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
if [[ ! -f .env ]]; then
    echo 'Falta .env. Sigue LEEME-PRIMERO.md para configurar una instalación nueva.' >&2
    exit 1
fi
docker info > /dev/null
bash acelerar.sh
echo 'Aplicación y programador iniciados: http://localhost:8000'
