# Estado de verificación — 26/11/2026, hora de Perú

## Verificación de la copia destinada a GitHub

El 26/11/2026 se verificó esta copia en Docker con la imagen local `sail-8.5/app`, PHP 8.5.12 y PostgreSQL 18. No se modificó la instalación operativa ni sus cuentas.

- Suite completa ejecutada en un contenedor sin red con SQLite en memoria: **76 pruebas, 941 comprobaciones, 0 errores y 0 fallos**.
- `composer validate --no-check-publish` y `composer check-platform-reqs`: correctos.
- Instalación de frontend con `npm ci --ignore-scripts`: correcta; la auditoría de esa ejecución informó 0 vulnerabilidades. `npm run build`: compilación Vite correcta.
- `docker compose config --quiet`: configuración válida. Se levantó un proyecto Compose separado, con volumen PostgreSQL nuevo y puertos 18080/15432/15173, sin utilizar el volumen de trabajo.
- Todas las migraciones aplicadas desde cero en PostgreSQL; 71 vistas verificadas; `php artisan optimize` correcto. Comprobación de integridad de la base vacía: 0 errores y 0 observaciones.
- Inicio de sesión HTTP real con cookies y CSRF sobre PostgreSQL: correcto. Respuestas HTTP 200 en salud, dashboard, productos, Kardex, requerimientos, usuarios, clientes, proyectos, reservas, ventas, cobros, planes, ayuda e integridad. Los assets CSS y JS también respondieron HTTP 200.
- La cuenta temporal de esa comprobación se creó únicamente en la base aislada y se retiró al terminar. Las cuentas existentes no se alteraron.

La prueba de instalación utilizó la imagen Sail ya disponible y las dependencias PHP de la entrega, validadas contra sus requisitos. No incluyó reconstruir la imagen Docker ni descargar todas las dependencias PHP desde cero. La suite funcional usa SQLite; la comprobación HTTP sobre PostgreSQL cubre acceso y lectura de módulos, no una prueba de concurrencia o carga. Todavía no se ha publicado un servidor de acceso por Internet ni migrado la base operativa.

El repositorio excluye `.env`, respaldos SQL, dependencias instaladas, datos privados, caches e informes de ejecución. La conservación de las cuentas al compartir la aplicación se explica en [Acceso para el equipo](COMPARTIR-CON-EL-EQUIPO.md).

## Pruebas de Laravel ejecutadas

**76 pruebas, 941 comprobaciones: todas correctas.** Se ejecutó la suite completa con PHP 8.5.13 portátil, descargado del sitio oficial y comprobado por SHA256. No se cambió el PHP global de Windows ni se modificaron las dependencias del proyecto.

Las pruebas utilizaron SQLite en memoria, configuración de testing forzada, clave exclusiva de pruebas, caché y sesiones en memoria y correo de prueba. Se mantuvo la guardia que impide preparar tablas si la base no está aislada. En este entorno Windows, el lanzador temporal precargó el mismo autoload de Composer porque `is_readable` fallaba pese a permitir la lectura; mantuvo los directorios y variables de phpunit.xml, sin omitir pruebas ni alterar vendor. En Docker, el comando habitual sigue siendo `bash probar.sh`.

El informe de la última ejecución está en `storage/app/private/reports/pruebas.xml`. Incluye acceso por DNI/correo, roles, stock, exportaciones, requerimientos y despachos, clientes, reservas, ventas, cuotas, adelantos, constancias, restauración de cuentas, importaciones y métricas. Se corrigieron fixtures de pruebas que omitían el responsable de creación y un límite horario que estaba expresado en UTC en lugar de Perú; se mantuvieron las protecciones de los modelos.

## Vistas e interacción

Todas las vistas Blade compilaron a PHP válido. La suite conserva una regresión que detecta instrucciones de cierre pegadas. Los assets CSS y JavaScript se compilaron correctamente y se comprobó el manifest.

Dashboard, Requerimientos e Historial general de cobros se renderizaron realmente mediante Laravel en una base aislada con datos ficticios. En navegador se probaron tres semanas, cambio a fechas personalizadas, pestañas de pago, búsqueda de materiales, apertura/cierre de formularios, revisión de solicitudes, resúmenes de importes e impresión. Se revisaron 320, 390 y 1440 píxeles, sin desborde horizontal de la página. Las tablas pueden desplazarse dentro de su contenedor. No se enviaron formularios de la vista de prueba al servidor operativo.

## PostgreSQL local

Las nuevas consultas inmobiliarias se ejecutaron en PostgreSQL con `REPEATABLE READ, READ ONLY`. Se comprobaron ventas, clientes, reservas, cobros por fecha de pago y moneda, solicitudes y entregas por semana. Para el 09/11/2026 al 25/11/2026, la lectura encontró dos ventas, PEN 23,100 cobrados, 24 solicitudes creadas y 12 entregas; son lecturas del estado guardado en ese momento.

La carga de almacén del turno anterior guardó 77 requerimientos y 90 movimientos con respaldo previo. Sus controles SQL dieron cero saldos negativos, cero entregas sin movimiento, cero vínculos incorrectos y ningún cambio en el stock final de productos. Su origen y alcance están en [Actividad del almacén](ACTIVIDAD-DEL-ALMACEN.md).

## Límites

La suite automatizada usa SQLite; no prueba bloqueos o concurrencia simultánea de varios usuarios en PostgreSQL. Las comprobaciones de lectura sobre PostgreSQL y de importación no equivalen a una prueba completa de carga o a una auditoría física y bancaria. Los datos generados de muestra no prueban operaciones históricas reales. No hay integración automática con RENIEC, SUNARP, SUNAT ni bancos.
