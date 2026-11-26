# Acceso para compañeros

## Una sola aplicación para todo el equipo

Para trabajar desde distintas computadoras y ver los mismos registros, se necesita una instalación central de Laravel y una base PostgreSQL compartida por esa aplicación. Los compañeros acceden con un navegador y su propia cuenta; no necesitan instalar Docker ni PostgreSQL.

GitHub guarda el código y permite colaborar. Subir este repositorio no publica una aplicación web ni copia los datos actuales. GitHub Pages no ejecuta este backend PHP.

## Conservar las cuentas que ya existen

Para este equipo se deben conservar las cuentas y contraseñas actuales. Al trasladar el sistema, se hace un respaldo completo de PostgreSQL y se restaura en el servidor nuevo mediante un canal privado. Así se conservan usuarios, contraseñas cifradas mediante hash, permisos y el historial relacionado. No se sustituyen las cuentas por usuarios de ejemplo ni se restablecen sus contraseñas.

También se conserva la `APP_KEY` de la instalación actual y se trasladan los archivos privados que el sistema necesite. El archivo `.env` se adapta al servidor nuevo sin publicar sus secretos. Antes de habilitar el acceso al equipo, se comprueba el inicio de sesión con una cuenta existente y se contrastan los registros restaurados. Durante el traslado se coordina una pausa de escrituras para evitar que queden cambios fuera del respaldo.

Los pasos de crear base vacía, generar clave y crear primer administrador de la sección siguiente solo corresponden a instalaciones nuevas. No deben ejecutarse para reemplazar una instalación restaurada. El respaldo de trabajo y las credenciales no se guardan en GitHub.

## Desde lugares distintos

La opción recomendada es un alojamiento compatible con PHP 8.5 y PostgreSQL 18, con una dirección HTTPS. Se instala una vez y permanece disponible aunque la laptop del autor esté apagada. El alojamiento puede tener un costo; todavía no se ha contratado ni publicado un servidor.

La persona responsable del despliegue debe:

1. Descargar el repositorio e instalar las dependencias de Composer y Node usando los archivos lock.
2. Crear una base PostgreSQL y configurar las variables privadas. Usar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://su-direccion` y cookies de sesión seguras bajo HTTPS. Generar `APP_KEY` una sola vez y conservarla.
3. Servir únicamente la carpeta `public`, compilar con `npm ci --ignore-scripts && npm run build`, aplicar `php artisan migrate --force` y ejecutar `php artisan optimize`.
4. Mantener las carpetas de almacenamiento y la base en discos persistentes, y configurar respaldos recuperables.
5. Ejecutar `php artisan schedule:run` cada minuto para vencer reservas y crear la primera cuenta con `php artisan almacen:crear-admin`.
6. Entrar como administrador y crear cuentas individuales en **Usuarios y permisos**. El registro público está cerrado.
7. Comprobar acceso, permisos, registro de operaciones y persistencia después de reiniciar antes de entregar el enlace.

El Compose incluido utiliza Laravel Sail para desarrollo local. Para publicarlo de forma permanente se necesita configurar el servidor, HTTPS y sus procesos; no basta con exponer los puertos del Compose a Internet. PostgreSQL y el panel de tablas deben quedar privados.

Referencia: [despliegue oficial de Laravel](https://laravel.com/framework/docs/13.x/deployment).

## En el mismo salón o red Wi-Fi

Para una demostración local puede ejecutarse la aplicación en una sola computadora siguiendo [Instalación nueva](INSTALACION-NUEVA.md). Con el puerto habitual 8000, los compañeros abren `http://IP-DE-LA-COMPUTADORA:8000`.

La computadora anfitriona debe estar encendida, Docker debe estar iniciado y el firewall debe permitir ese puerto en la red privada. Algunas redes de institutos bloquean la comunicación entre equipos. `localhost` en cada computadora apunta a esa misma computadora; no sirve como enlace compartido.

En la computadora anfitriona ajusta `APP_URL` a su dirección de red y limpia la configuración con `php artisan optimize:clear` dentro del contenedor. Esta alternativa depende de la laptop y de la red local; no proporciona acceso permanente desde otras casas.

## Para que cada compañero programe

Cada integrante puede clonar el repositorio y seguir [Instalación nueva](INSTALACION-NUEVA.md). Cada instalación tendrá su propia base vacía; los registros no se sincronizan por Git. Se comparten cambios de código mediante ramas y revisiones. Las credenciales se configuran por separado y no se suben al repositorio.
