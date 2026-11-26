# Instalación en otra computadora

Este procedimiento es para una copia nueva. Para el proyecto que ya usas, sigue LEEME-PRIMERO.md; no reemplaces su .env ni regeneres su clave.

Requiere Docker Desktop con motor Linux, WSL2 y Ubuntu, e Internet para descargar imágenes y dependencias. Abre esta carpeta desde Ubuntu en Visual.

## 1. Configuración inicial

Copia `.env.example` a `.env` solo si no existe. Ajusta nombre de base, usuario, contraseña y puerto antes de inicializar PostgreSQL. La clave APP_KEY se generará después. Conserva .env en privado.

```bash
cp -n .env.example .env
```

## 2. Dependencias y contenedores

Para preparar vendor sin instalar PHP en Windows:

```bash
docker run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD:/app" -w /app composer:2 install --ignore-platform-reqs --no-scripts --no-interaction
docker compose build laravel.test
docker compose up -d pgsql laravel.test
```

La omisión de requisitos solo sirve para esa preparación inicial. Ahora verifica e instala dentro del PHP del contenedor y genera la clave, únicamente en esta instalación nueva:

```bash
docker compose exec --user sail laravel.test composer install --no-interaction
docker compose exec --user sail laravel.test php artisan key:generate
docker compose exec --user sail laravel.test npm ci --ignore-scripts
bash actualizar.sh
```

El actualizador respalda la base, aplica migraciones, compila y activa el programador. Las imágenes y paquetes deben poder descargarse; si hay un fallo de DNS o credenciales de Docker, corrige esa conexión antes de seguir. No se debe borrar el volumen como solución a un fallo de descarga.

## 3. Primera cuenta

```bash
docker compose exec --user sail laravel.test php artisan almacen:crear-admin
```

Introduce nombre, correo y contraseña privada. El sistema no crea una contraseña universal. En Usuarios y permisos puedes completar tu DNI y crear operadores.

## 4. Pruebas y ejemplos

```bash
bash probar.sh
```

Las pruebas usan una base en memoria, no PostgreSQL operativo. Si necesitas clientes, lotes y materiales ficticios para presentar:

```bash
bash cargar-ejemplos.sh
```

La carga no crea pagos ficticios ni nuevas contraseñas; las reservas y el stock se registran por sus acciones del sistema. Consulta VERIFICACION.md para saber qué comprobaciones ya se realizaron en esta entrega y cuáles faltan en Docker.

## 5. Inicio diario

Con Docker abierto:

```bash
bash iniciar.sh
```

Abre http://localhost:8000. El panel de tablas es opcional: `bash ver-datos.sh`, luego http://localhost:8081/?pgsql=pgsql. No modifiques saldos o cobros por fuera de la aplicación.
