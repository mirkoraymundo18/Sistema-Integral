# Dashboard de la inmobiliaria y requerimientos

## Periodo de consulta

El inicio muestra las **tres semanas calendario más recientes, incluida la actual**. La última puede estar incompleta. El rango exacto se muestra junto a los filtros, en hora de Perú. Hay accesos a tres semanas, tres meses y seis meses; también puedes elegir fechas y agrupar por semanas o meses, hasta un año.

No se rellenan con cifras inventadas las semanas sin operaciones. Se muestran ceros cuando no hay registros en esas fechas.

## Significado de los indicadores

| Indicador | Qué muestra | Fecha o referencia |
| --- | --- | --- |
| Ventas confirmadas | Número de compras y precio acordado, separado por moneda | Registro de la venta dentro del periodo |
| Dinero cobrado | Pagos efectivamente registrados | Fecha del pago, aunque se haya digitado después |
| Saldo por cobrar | Deuda que sigue pendiente en todas las compras | Situación actual |
| Lotes disponibles, reservados y vendidos | Estados registrados del inventario inmobiliario | Situación actual |
| Clientes nuevos | Fichas de clientes creadas | Fecha de registro dentro del periodo |
| Reservas creadas | Reservas registradas, cualquiera que sea su estado actual | Fecha de creación dentro del periodo |
| Reservas convertidas | Porcentaje de las reservas creadas en el periodo que actualmente tienen compra confirmada | Cohorte del periodo, estado al día de hoy |
| Cuotas vencidas | Cuotas con saldo y vencimiento anterior a hoy | Situación actual |
| Solicitudes creadas | Requerimientos registrados | Fecha de solicitud dentro del periodo |
| Entregas realizadas | Solicitudes entregadas vinculadas a una salida de Kardex | Fecha del movimiento de entrega |

Una venta acordada no es dinero cobrado. Los soles y dólares se presentan separados. El valor del catálogo de materiales no es utilidad de la inmobiliaria. El sistema no calcula rentabilidad neta, porque no tiene un registro completo de gastos y costos de adquisición de inmuebles.

## Consultar el detalle

- Cada barra de cobros abre el **Historial general de cobros** con esas fechas y moneda.
- El historial permite filtrar por fecha, moneda, cliente o referencia y abrir la constancia de cada pago.
- Cada proyecto abre su inventario de lotes.
- La evolución muestra clientes, reservas, ventas, solicitudes y entregas en las mismas filas semanales o mensuales.
- El número de solicitudes de una fila abre Requerimientos con esas fechas. Las entregas pueden corresponder a solicitudes anteriores; por eso sus cantidades se muestran por separado.
- La cartera muestra vencidos actuales y cuotas de los próximos siete días. Estos avisos no se limitan al periodo histórico seleccionado.
- El gráfico de Kardex queda en un apartado desplegable de almacén.

## Requerimientos: secuencia del trabajo

La bandeja muestra pendientes y aprobados primero. Cada fila resume material, cantidad, solicitante, fecha y estado. Pulsa **Gestionar** o **Ver detalle** para desplegar motivo, revisión y entrega, sin abrir decenas de tarjetas completas.

1. **Nueva solicitud:** busca el producto en el catálogo, revisa la unidad, cantidad y disponibilidad e indica el frente de trabajo.
2. **Revisión:** administración aprueba o rechaza y puede dejar un comentario. Aprobar no descuenta stock.
3. **Entrega:** administración revisa disponibilidad y confirma cuando retira el material. Se descuenta stock y se genera una sola salida vinculada a la solicitud.
4. **Comprobante:** en la solicitud entregada puedes abrir el comprobante y su movimiento de Kardex.

El operador consulta y busca únicamente sus solicitudes. Administración ve todas. Los filtros de búsqueda aceptan material, SKU, solicitante, motivo y códigos como SOL-000078. El filtro de fechas corresponde a la fecha de solicitud. Los contadores respetan la búsqueda y fechas; el estado elegido limita la lista.

## Verificación de esta mejora

Se ejecutaron 76 pruebas de Laravel con 941 comprobaciones sobre SQLite en memoria, incluidas sintaxis real de todas las vistas Blade, límites de fecha en Perú, monedas separadas, permisos, despacho, cuotas y pagos. Las nuevas consultas de métricas también se contrastaron con PostgreSQL local dentro de una transacción de solo lectura. Se revisaron las pantallas renderizadas por Laravel y sus interacciones en anchos de 320, 390 y 1440 píxeles.
