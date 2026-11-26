# Cobros y deudas

## Recorrido del personal

1. Entra a **Clientes** y busca por DNI o nombre.
2. Abre la ficha. **Compras y deudas** muestra el total comprado, lo pagado y el saldo, separado por moneda. Las compras pagadas siguen visibles.
3. En la compra correspondiente pulsa **Pagar o adelantar**. Puedes pagar el siguiente vencimiento, adelantar varias cuotas o registrar otro importe.
4. Si el comprador paga todo, pulsa **Cancelar toda la deuda**. Se prepara el saldo completo; todavía no se registra dinero.
5. Revisa la fecha del pago y elige efectivo, transferencia o depósito. En los dos últimos es obligatorio el número de operación.
6. Revisa cómo se distribuirá el importe y confirma únicamente dinero recibido. El registro actualiza el saldo y las cuotas.
7. Se abre el historial con acceso a **Ver constancia del pago**. También puedes encontrar cada pago en la ficha del cliente y en la compra. La constancia se puede imprimir o guardar como PDF desde el navegador.

También puedes empezar desde **Cobros y deudas**, en el menú comercial. Busca por nombre, apellido, DNI o código VEN-000001; filtra pendientes, vencidas o canceladas. Los atajos de estado abren una consulta general nueva; la ficha mantiene el filtro del cliente.

## Organización de la pantalla de pago

El resumen superior muestra saldo, porcentaje pagado e inmueble. Debajo hay tres pestañas: **Registrar pago**, **Cronograma** e **Historial**. El operador consulta las dos últimas. El formulario se divide en tipo de pago, importe/fecha y medio de pago. El resumen lateral actualiza el importe y el saldo resultante; la distribución por cuotas se despliega cuando la necesitas.

Para cancelar toda la deuda, abre **Gestionar pago → Tipo de pago → Cancelar toda la deuda**. Desde la ficha del cliente también se conserva el acceso directo. En el cronograma, **Cubrir hasta aquí** abre el formulario con las cuotas correspondientes. La pestaña Historial permite consultar las constancias. Las pestañas admiten teclado (flechas, Inicio y Fin) y enlaces directos; al imprimir se incluyen cronograma e historial de las páginas consultadas.

## Qué conserva cada pago

- Código del pago y de la compra.
- Importe y moneda.
- Fecha del pago y fecha/hora de registro en Perú, que pueden ser distintas.
- Medio de pago, operación/referencia, observaciones y usuario que registró.
- Distribución del importe entre inicial y cuotas, cuando hay cronograma.

Ejemplo: después de pagar una inicial de S/ 4,500, adelantar tres cuotas de S/ 1,687.50 propone S/ 5,062.50. Se conserva un pago con tres aplicaciones al cronograma. Una cancelación total paga todos los saldos pendientes; no elimina la venta ni sus registros.

El operador consulta deudas, cronogramas e imprime constancias. Administración registra cobros. Una reserva sola no registra un pago ni una deuda de compra. La constancia es interna; la referencia ingresada no verifica automáticamente el dinero con un banco.

## Activar estos cambios

Desde Ubuntu y la carpeta de este proyecto, con los contenedores iniciados:

```bash
docker compose exec laravel.test php artisan optimize:clear
docker compose exec laravel.test php artisan sistema:verificar-vistas
```

Los archivos de interfaz ya están compilados en esta entrega. Recarga con Ctrl + F5. Esta mejora no requiere tablas nuevas. Para recompilar posteriormente puedes ejecutar `bash actualizar.sh`.

Pruebas de regresión añadidas en `CollectionsWorkspaceTest`: monedas separadas, compras pagadas visibles, cancelación total sin duplicar cobro, constancia correspondiente a su venta, permisos y búsqueda. Ejecuta `bash probar.sh --filter CollectionsWorkspaceTest` para comprobarlas en Docker. Estas pruebas pasaron con PHP 8.5.13 sobre SQLite en memoria, junto con la suite completa. El historial general de cobros permite además consultar pagos de varias compras por fecha, moneda y cliente.
