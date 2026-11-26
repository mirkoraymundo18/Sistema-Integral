# Sistema Integral — Leidy Inmobiliaria

Aplicación Laravel con PostgreSQL y Docker para control de almacén y atención comercial. Incluye productos por familia/unidad, Kardex, stock mínimo, requerimientos, usuarios y permisos; además, clientes, proyectos/lotes, reservas, ventas, cuotas y pagos.

**Empieza por [LEEME-PRIMERO.md](LEEME-PRIMERO.md).** Abre `Leidy-Inmobiliaria.code-workspace` para trabajar en la carpeta correcta.

```bash
bash iniciar.sh
bash actualizar.sh
```

Aplicación: http://localhost:8000. Pruebas aisladas: `bash probar.sh` o Ctrl + Shift + B en Visual. Respaldo: `bash respaldar.sh`.

## Documentación

- [Acceso desde las computadoras del equipo](docs/COMPARTIR-CON-EL-EQUIPO.md).
- [Guía de inicio y uso](LEEME-PRIMERO.md).
- [Cobros, cancelación de deudas e historial](docs/COBROS-Y-DEUDAS.md).
- [Actividad del almacén de septiembre a noviembre](docs/ACTIVIDAD-DEL-ALMACEN.md).
- [Dashboard inmobiliario y requerimientos](docs/DASHBOARD-Y-REQUERIMIENTOS.md).
- [Instalación nueva](docs/INSTALACION-NUEVA.md).
- [Arquitectura, datos y permisos](docs/ENTREGA-TECNICA.md).
- [Estado real de verificación](docs/VERIFICACION.md).
- [Historial detallado de ampliaciones](docs/HISTORIAL-DE-CAMBIOS.md).

En la aplicación, **Guía de uso** explica los recorridos y permite a administración comprobar integridad sin modificar datos.

## Entorno

Docker Desktop con motor Linux, WSL2/Ubuntu y las dependencias del proyecto. PHP compatible: 8.4.1 o superior; la imagen de trabajo utiliza PHP 8.5. PostgreSQL 18. No hace falta reemplazar tu PHP de Windows para usar la aplicación mediante Docker.

La compilación habitual es `npm run build` dentro del contenedor. Si una copia de dependencias contiene binarios de otro sistema operativo, `npm run build:portable` genera un bundle local sin descargas a partir de los módulos instalados y Tailwind. El actualizador intenta primero Vite y utiliza esa alternativa si falla. El paquete de entrega incluye assets compilados.

## Datos y límites

Las pruebas predeterminadas se bloquean salvo que usen entorno testing y SQLite en memoria. No se cambia la base operativa para ejecutarlas. Los scripts de actualización respaldan PostgreSQL antes de migrar. El servicio `scheduler` procesa reservas vencidas mientras Docker está funcionando; no hace falta dejar una terminal abierta.

El PDF original se centra en almacén. La gestión comercial se añadió por solicitud del usuario y sus documentos son internos. No hay conexión oficial con RENIEC, SUNARP, SUNAT o bancos ni emisión notarial. La documentación distingue funciones implementadas, comprobaciones realizadas y pruebas todavía pendientes.
