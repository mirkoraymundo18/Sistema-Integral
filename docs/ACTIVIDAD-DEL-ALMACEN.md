# Tres meses de actividad de almacén

## Datos guardados el 25 de noviembre de 2026, hora de Perú

Se añadieron a PostgreSQL 77 requerimientos y 90 movimientos, desde septiembre hasta el 25 de noviembre. Se conservaron los 77 productos, sus unidades, precios y saldos anteriores. No se crearon ventas, deudas de clientes ni cobros para rellenar los indicadores.

| Mes | Abastecimientos nuevos | Entregas nuevas |
| --- | ---: | ---: |
| Septiembre | 8 | 16 |
| Octubre | 10 | 20 |
| Noviembre | 12 | 24 |
| Total | 30 | 60 |

Además de las 60 solicitudes entregadas, hay 9 rechazadas, 4 aprobadas por entregar y 4 pendientes de revisión. Sumada la solicitud anterior, la base tiene 78 requerimientos y 158 movimientos de Kardex. Los gráficos incluyen también las operaciones que ya estaban registradas; por eso noviembre muestra más movimientos que los de esta tabla.

Los motivos se refieren a abastecimiento, vías internas, cercado, instalaciones sanitarias, oficina de ventas, seguridad y mantenimiento. Cada solicitud entregada tiene su salida de Kardex y comprobante accesible. Las pendientes y aprobadas siguen siendo operables desde la plataforma; sus futuras entregas sí descontarán stock.

## Origen y trazabilidad

Son registros de muestra generados, persistidos en la base de la plataforma. Sus fechas, cantidades y decisiones no proceden de documentos de operaciones reales. La etiqueta `GENERATED_SAMPLE` y los identificadores se conservan en `warehouse_activity_batches`, fuera de los motivos operativos visibles. No se presentan como evidencia de trabajo histórico de la empresa.

Se cambiaron 48 motivos de la carga inicial a «Registro de existencias iniciales». El texto anterior y los identificadores se conservan en los metadatos. No se modificaron otros motivos escritos por el personal. Antes de aplicar la carga se guardó un respaldo JSON de las tablas de almacén en `storage/app/private/backups`; no es un respaldo completo de clientes, ventas o usuarios.

Los abastecimientos añadidos cubren exactamente las entregas históricas añadidas: el saldo final de cada producto se mantiene. Esto no significa que una entrega nueva real no descuente stock.

## Consultar en la aplicación

- Dashboard: agrupación **Meses**, **3 periodos**, o el enlace **Ver últimos 3 meses**. El rango inicial del dashboard es ahora tres semanas; puedes pasar a tres meses con ese acceso.
- Cada barra abre su mes y tipo de movimiento en Kardex.
- Requerimientos: filtros por estado; las entregas enlazan su movimiento y comprobante.
- Los listados se ordenan por fecha del registro y luego por ID para mantener coherencia con el historial cargado.

## Repetición y otras instalaciones

En otra instalación, con catálogo, cuenta administradora y Docker iniciado:

```bash
bash cargar-actividad.sh --hasta=2026-11-25
```

El script respalda PostgreSQL, aplica las migraciones pendientes y ejecuta la carga. En esta instalación los datos ya están cargados; no hace falta repetirlo. Cada mes se identifica de manera estable, incluso si se consultan periodos superpuestos. Repetir la carga no vuelve a sumar sus abastecimientos o solicitudes. Si faltan los metadatos de un mes que todavía tiene movimientos de la carga, el importador se detiene para evitar duplicarlos.

La tabla de trazabilidad ya fue creada en esta base con el esquema de la migración incluida. Una ejecución posterior de `artisan migrate` registrará esa migración sin volver a crear la tabla.

## Comprobaciones

Ejecutadas sobre PostgreSQL después de la carga: cero saldos negativos, cero entregas sin movimiento, cero vínculos con producto/cantidad/tipo incorrectos y cero productos cuyo saldo final haya cambiado. La lectura mensual se comprobó en hora de Perú. Se probaron de forma aislada con SQLite la carga, repetición, meses superpuestos, conservación de saldo y rollback por límite de stock.

`WarehouseQuarterTest` pasó dentro de la suite completa de 76 pruebas de Laravel sobre SQLite en memoria. La comprobación directa de PostgreSQL y los límites de concurrencia están descritos en VERIFICACION.md.
