# Leidy Inmobiliaria: empezar aquí

## Abrir e iniciar

Abre `Leidy-Inmobiliaria.code-workspace` en Visual Studio Code. Esa es la carpeta que contiene todos los cambios. Abre Docker Desktop y espera a que esté listo.

En una terminal Ubuntu, desde esta carpeta:

```bash
bash iniciar.sh
bash actualizar.sh
```

El primer comando inicia la aplicación con las dependencias en almacenamiento Linux; si falta preparar esa copia, la primera ejecución puede tardar. El segundo guarda un respaldo SQL, aplica las tablas pendientes, compila las pantallas y activa el programador de reservas. Si los contenedores ya estaban iniciados, basta con `bash actualizar.sh`.

Abre **http://localhost:8000**. Usa tu DNI o correo y contraseña habituales. No existe una contraseña universal de administrador. En una instalación nueva, sigue [Instalación nueva](docs/INSTALACION-NUEVA.md).

## Probar sin tocar tus datos

En Visual presiona **Ctrl + Shift + B** y ejecuta «Verificar proyecto sin tocar datos». También puedes usar:

```bash
bash probar.sh
```

Las pruebas usan SQLite en memoria y una clave ficticia exclusiva de pruebas. No utilizan PostgreSQL operativo, ni su caché o correo. Si la configuración no está aislada, se detienen antes de preparar la base. El resultado queda en `storage/app/private/reports/pruebas.xml`.

**Importante:** ejecutar estas pruebas no significa que se haya probado concurrencia real con PostgreSQL. El estado de las comprobaciones de esta entrega está en [Verificación](docs/VERIFICACION.md).

## Recorrido principal

1. **Productos:** registra familia, unidad, precio y mínimo. El SKU se genera automáticamente.
2. **Kardex:** registra entradas y salidas. El saldo se actualiza; no se permite sacar más stock del disponible.
3. **Requerimientos:** operador solicita; administración aprueba y despacha una sola vez. La entrega genera su salida y comprobante.
4. **Clientes:** busca o registra al comprador con datos manuales. Desde su ficha puedes reservar un lote disponible.
5. **Reservas:** revisa precio y vigencia. Administración puede confirmar la compra; operador puede simular contado o cuotas.
6. **Ventas y pagos:** elige una compra pendiente del cliente. «Pagar o adelantar» propone el importe según su cronograma y la fecha actual de Perú.
7. **Cuotas:** siguiente vencimiento, varias cuotas, otro importe o todo el saldo. Revisa la distribución antes de confirmar el dinero recibido.

El botón **Volver** aparece arriba de los apartados. En celular usa el botón de menú para cambiar de módulo. Dentro de la aplicación encontrarás **Guía de uso**.

## Ejemplo de presentación

Para consultar o cancelar una deuda, sigue [Cobros y deudas](docs/COBROS-Y-DEUDAS.md): ficha del cliente → compra → pago → historial y constancia.

Precio de lote S/ 45,000; inicial de 10% = S/ 4,500; saldo S/ 40,500 en 24 cuotas de S/ 1,687.50. Una vez pagada la inicial, adelantar tres cuotas propone S/ 5,062.50. Los abonos cubren primero los pendientes más antiguos y no cambian las fechas acordadas.

Si necesitas los datos ficticios de presentación y todavía no los cargaste:

```bash
bash cargar-ejemplos.sh
```

Conserva registros anteriores y no vuelve a sumar stock a productos existentes. Si las reservas de ejemplo vencieron, crea otras desde los lotes disponibles: la carga no reactiva reservas canceladas o vencidas.

## Mantenimiento

- Respaldo manual: `bash respaldar.sh`. Se guarda en `storage/app/private/backups`.
- Revisar integridad: abre **Guía de uso → Comprobar integridad de datos**, como administrador. Es de solo lectura.
- Ejecutar la misma revisión en consola: `docker compose exec --user sail laravel.test php artisan sistema:verificar`.
- Reactivar una cuenta: **Usuarios y permisos → Estado: Desactivadas → Reactivar cuenta**. Conserva su historial y credenciales.
- Ver tablas: `bash ver-datos.sh` y http://localhost:8081/?pgsql=pgsql. Usa las credenciales de la base, no las del usuario de la aplicación.
- Detener conservando datos: `docker compose stop`.

No uses `docker compose down -v`: elimina los volúmenes. No compartas `.env`, respaldos SQL ni informes con datos reales. Registra saldos y cobros desde la aplicación; editar tablas directamente puede romper la trazabilidad.

## Alcance de la entrega

Se entrega un sistema local para almacén y la ampliación comercial solicitada: clientes, lotes, reservas, venta interna, contado/cuotas y cobros. Los planes iniciales son referenciales y editables, sin intereses automáticos. No hay verificación automática con RENIEC/SUNARP, facturación SUNAT, conciliación bancaria, refinanciación, devoluciones o anulación de ventas. Esas integraciones requieren condiciones y accesos externos; no se simulan como si estuvieran conectadas.
