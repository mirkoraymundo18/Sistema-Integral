# Almacén de Leidy Inmobiliaria

Sistema interno de almacén desarrollado con Laravel, Blade, Vite y PostgreSQL. Abre la carpeta `almacen-inmobiliaria` en Visual Studio Code.

## Primer arranque con Docker Desktop y WSL

En una terminal de Ubuntu en WSL, dentro de la carpeta del proyecto, comprueba que `docker info` funciona. Si esta es una instalación nueva, prepara el entorno y las dependencias PHP.

```bash
cp .env.example .env
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/app" -w /app composer:2 install --ignore-platform-reqs --no-scripts
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
./vendor/bin/sail artisan almacen:crear-admin
```

El primer `composer install` prepara los archivos de Sail. El segundo valida las dependencias dentro del contenedor de PHP. La aplicación queda en [http://localhost:8000](http://localhost:8000) con los valores del ejemplo. Si el puerto web está ocupado, ajusta `APP_PORT` y `APP_URL` en `.env`. Si el puerto 5432 está ocupado en Windows, ajusta `FORWARD_DB_PORT` y conserva `DB_PORT=5432` para la conexión entre contenedores.

El comando `almacen:crear-admin` solicita el nombre, correo y contraseña. Luego puedes crear las cuentas del equipo desde **Usuarios y Permisos**.

## Actualizar una instalación existente

Haz un respaldo de PostgreSQL antes de integrar los cambios. Conserva el `.env`, la clave de aplicación y el volumen de tu instalación. Integra los archivos fuente en tu carpeta de trabajo y ejecuta las migraciones pendientes y la compilación de frontend.

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

Si tu instalación todavía carece de una cuenta administrativa activa, usa `./vendor/bin/sail artisan almacen:crear-admin`. La migración conserva los saldos y los registros existentes. Los movimientos anteriores aparecen como «Sin responsable registrado» cuando el sistema carece de ese dato. Los productos nuevos empiezan con saldo cero y reciben el saldo inicial mediante una entrada en Kardex.

## Dashboard y existencias

El panel resume el stock activo, el valor estimado, la reposición y las solicitudes que requieren atención. El valor estimado usa el stock actual multiplicado por el precio unitario del catálogo. El gráfico cuenta movimientos por mes y permite abrir en Kardex las entradas o salidas de ese periodo. El Excel de existencias se puede descargar completo desde el panel o con los filtros activos desde el catálogo.

## Catálogo de productos

En **Productos → Familias y unidades**, crea una familia con un prefijo único y al menos una unidad. Después, **Nuevo producto** genera el SKU y abre una ficha con el saldo, el mínimo, el faltante y los movimientos recientes. Desde esa ficha puedes registrar la entrada inicial en Kardex.

Los productos existentes conservan sus códigos y saldos. Se les puede asignar familia y unidad desde **Editar ficha**. El mínimo inicial para los productos existentes es 5 y se puede ajustar individualmente. Una familia asignada conserva el SKU y la unidad permanece fija cuando ya hay stock o historial. El filtro **Por reponer** incluye los productos que alcanzaron su mínimo o están por debajo.

## Kardex

Selecciona **Registrar entrada** o **Registrar salida**. El formulario muestra el saldo disponible y el saldo previsto antes de guardar. El servidor verifica la disponibilidad dentro de la transacción. El historial permite combinar filtros de producto, tipo y fechas, y **Descargar Excel** exporta todos los movimientos de esa consulta.

## Acceso con DNI

En Usuarios y permisos, el administrador puede registrar un DNI de 8 dígitos en la ficha de cada cuenta. El ingreso acepta ese DNI o el correo junto con la contraseña. El DNI es opcional, único incluso para las cuentas desactivadas y conserva los ceros iniciales. El correo se utiliza también para la recuperación de contraseña.

## Permisos de esta versión

| Acción | Administrador | Operador |
| --- | --- | --- |
| Consultar el catálogo y registrar movimientos en Kardex | Sí | Sí |
| Crear, editar y archivar productos. Configurar familias y unidades | Sí | |
| Crear solicitudes | Sí | Sí |
| Ver solicitudes | Todas | Las propias |
| Revisar y despachar solicitudes | Sí | |
| Crear, editar y desactivar cuentas | Sí | |

En **Requerimientos**, el operador solicita una cantidad entera y ve sus estados. El administrador revisa y aprueba o rechaza. Al confirmar una entrega desde **Por entregar**, el sistema comprueba el stock, registra una sola salida vinculada en Kardex y marca la solicitud como entregada. Para archivar un producto se requiere saldo cero y ausencia de solicitudes por revisar o entregar. Los movimientos y las solicitudes conservan sus referencias a productos archivados y usuarios desactivados.

## Activar las ampliaciones del almacén

Con los contenedores encendidos, ejecuta bash actualizar.sh desde Ubuntu en esta carpeta. El comando guarda un respaldo SQL en storage/app/private/backups, aplica las migraciones, compila las pantallas y actualiza la caché.

## Control de inventario

- **Conteo físico** es una opción del administrador. Registra el saldo encontrado y el motivo. La diferencia genera un movimiento de ajuste en Kardex. Si el saldo cambió desde que se abrió el conteo, se debe actualizar y volver a verificar.
- **Reposición** lista los productos en el mínimo o por debajo. Permite exportar a Excel el stock, el mínimo y el faltante por unidad.
- **Familias y unidades** permite habilitar hasta dos decimales en una unidad. Metros, kilogramos y litros existentes se habilitan al migrar. Las unidades restantes requieren cantidades enteras hasta que un administrador las habilite.
- **Requerimientos entregados** incluye un comprobante para imprimir o guardar como PDF desde el navegador. El solicitante y el administrador pueden verlo.
- La sesión vence tras 30 minutos sin peticiones autenticadas al sistema. Al volver a interactuar se pide iniciar sesión.

## Proyectos y lotes

El menú Comercial permite registrar proyectos con su ubicación y organizar lotes por manzana y número. El administrador crea y edita las fichas y el operador las consulta. Los lotes empiezan disponibles. Cada cambio manual a reservado o vendido requiere un motivo y conserva el responsable de la última actualización.

Se valida que la identificación de un lote sea única dentro de su proyecto. Si otra persona modifica una ficha mientras se edita, hay que volver a abrirla antes de guardar. Los precios se registran por lote en soles o dólares. Esta primera etapa cubre el catálogo comercial. Clientes, reservas con vencimiento, contratos y pagos corresponden a las siguientes etapas.

## Verificación

```bash
./vendor/bin/sail test
```

La suite espera una base PostgreSQL separada llamada `testing`. Sail la crea al inicializar un volumen nuevo con el script montado en Compose. En una instalación existente verifica que esa base de pruebas esté disponible antes de ejecutar la suite.

Para una prueba manual, crea un producto, registra una entrada de 10 unidades y dos salidas de 7 unidades desde sesiones diferentes. El resultado esperado es una salida aceptada, una rechazada y un saldo de 3. Comprueba que el historial conserve el nombre de quien registró cada movimiento aceptado.

## Pendientes según la guía del grupo

- Permisos más detallados que los dos roles actuales.
- Conciliación de saldos antiguos que pudieron haberse cargado directamente en el catálogo. El Kardex respeta la precisión configurada para cada unidad.
- La guía indica PostgreSQL 16. Compose conserva PostgreSQL 18, la versión del proyecto recibido. Coordina cualquier cambio de versión mayor con una instalación o un volumen separado.


## Clientes y reservas de lotes

Actualización: con Docker y los contenedores iniciados, abre la terminal Ubuntu en esta carpeta y ejecuta `bash actualizar.sh`. El script guarda un respaldo SQL, aplica migraciones y compila las pantallas sin reiniciar los datos.

1. Ingresa como administrador u operador y abre **Comercial > Clientes > Nuevo cliente**.
2. Registra DNI de 8 dígitos, nombres y apellidos. Los datos de contacto son opcionales. El DNI se almacena como texto para conservar ceros iniciales y no admite duplicados.
3. Abre un lote disponible en **Proyectos y lotes** y selecciona **Reservar este lote**, o entra a **Reservas > Nueva reserva**.
4. Elige el cliente, confirma el precio total y la fecha límite. La moneda corresponde al lote y la vigencia termina a las 23:59, hora de Perú.
5. Desde la ficha puedes imprimir o guardar la constancia como PDF. Conserva los datos del cliente y lote al momento de registrar la reserva, aunque luego sus fichas cambien.
6. Para liberar antes el lote, cancela la reserva indicando un motivo. Se conserva el historial. Una reserva activa bloquea la edición manual del lote y otras reservas sobre ese mismo lote.

Clientes, reservas y constancias están disponibles para administrador y operador. Ambos consultan, registran y actualizan clientes, registran reservas y obtienen constancias. El operador cancela únicamente sus propias reservas; el administrador cancela cualquiera, siempre indicando motivo. Las reservas anteriores registradas solo como estado manual de lote no se convierten en reservas de clientes automáticamente.

### Vencimiento

Al consultar proyectos, lotes, reservas o la ficha de un cliente, el sistema procesa los vencimientos y libera los lotes. También existe el comando `php artisan comercial:vencer-reservas`, programado cada minuto en Laravel. Para que se procese aun sin usuarios navegando, debe ejecutarse el scheduler: en otra terminal Ubuntu, desde la carpeta del proyecto, `docker compose exec --user sail laravel.test php artisan schedule:work`. Mantén esa terminal abierta; al reiniciar la computadora debes iniciarlo nuevamente. En un servidor se configura un proceso permanente o cron.

### DNI y datos oficiales

Esta versión permite registro manual y comprueba formato y duplicidad del DNI. **No consulta RENIEC, no obtiene nombres oficiales ni verifica la titularidad en SUNARP.** Ningún registro se marca como identidad verificada. Para autocompletar datos se necesita acceso autorizado de RENIEC o un proveedor habilitado; la conexión se implementará cuando se disponga del servicio y su documentación. Las claves deben permanecer en el servidor.

Referencias oficiales: [solicitud de acceso a RENIEC](https://www.gob.pe/13543-solicitar-acceso-al-servicio-de-verificacion-de-identidad-de-personas-en-reniec) y [publicidad registral de SUNARP](https://www.gob.pe/institucion/sunarp/pages/709-acceder-a-la-plataforma-de-servicio-de-publicidad-registral-en-linea-sprl). Una constancia interna de reserva no acredita propiedad ni sustituye documentos registrales.

### Verificación de esta ampliación

Pruebas funcionales incluidas en `tests/Feature/LotReservationTest.php`: DNI duplicado y ceros iniciales, reenvío sin duplicar, bloqueo de doble reserva, cancelación, conservación de la ficha original, vencimiento en hora de Perú y permisos. Para ejecutarlas en Docker con la base **testing**, usa `docker compose exec --user sail laravel.test php artisan test --filter=LotReservationTest`. No ejecutes pruebas con una configuración que apunte a la base operativa. En la sesión de edición se revisó sintaxis y compilación CSS; no se ejecutaron estas pruebas contra Docker por falta de acceso al motor desde la herramienta.


## Uso más sencillo de reservas y constancias

- **Desde un cliente:** pulsa «Reservar un lote». El cliente aparece preseleccionado.
- **Formulario guiado:** busca clientes por DNI o nombre y lotes por proyecto, manzana o número. La búsqueda reconoce acentos. El precio y la moneda se cargan desde el lote; puedes ajustar el precio total acordado.
- **Plazo rápido:** los botones 3, 7 y 15 días calculan la fecha desde el día actual en Perú. Puedes elegir otra fecha manualmente.
- **Antes de guardar:** el resumen muestra cliente, lote, área, precio y vigencia. Al confirmar aparece una revisión final; «Seguir editando» no guarda la reserva. El botón de envío se bloquea al enviar para evitar clics repetidos.
- **Constancia:** membrete, código, estado, datos agrupados, precio y plazo destacados. «Imprimir / Guardar PDF» abre el diálogo del navegador. La impresión A4 oculta la navegación y las acciones. Si hay condiciones extensas, el documento continúa en otras páginas.
- **Cancelar:** exige motivo y una confirmación adicional antes de liberar el lote.

Las mejoras funcionan en pantallas de escritorio y celular. Clientes y reservas se gestionan desde una cuenta administradora u operadora, con cancelación limitada al creador cuando se trata de un operador. Esta ampliación no crea un portal público ni cambia la conexión pendiente con RENIEC.

Verificación visual de esta revisión: vistas de ejemplo con datos ficticios a 320, 390 y 1440 px, impresión, búsqueda con y sin coincidencias, acentos, precio editado conservado al filtrar, cambio de moneda, cálculo del plazo y revisión antes del envío. La compilación de CSS y la revisión de sintaxis de JavaScript/PHP pasaron. La integración Laravel/PostgreSQL sigue pendiente de ejecución dentro de Docker.


## Ver la base de datos con botones (Adminer)

Con Docker Desktop abierto, ejecuta `bash ver-datos.sh` en Ubuntu desde la carpeta del proyecto. La primera vez se descarga la imagen oficial `adminer:6.3.0`. Abre http://localhost:8081/?pgsql=pgsql y elige el idioma Español si lo necesitas.

El sistema es PostgreSQL y el servidor es `pgsql`. Usa el usuario de `DB_USERNAME`, la base de `DB_DATABASE` y la contraseña de `DB_PASSWORD` de tu archivo `.env`. El script muestra usuario y base del contenedor sin imprimir contraseñas. Si cambiaste credenciales en `.env` después de inicializar PostgreSQL, utiliza las credenciales vigentes de esa base; cambiar `.env` no cambia automáticamente la contraseña almacenada en PostgreSQL.

Pulsa «Seleccionar datos» junto a `clients`, `land_lots`, `lot_reservations` o `products` para ver sus registros. En algunas traducciones aparece como «Seleccionar». Los formularios de la aplicación siguen siendo el lugar para registrar movimientos y reservas con sus validaciones.

El panel utiliza el volumen existente de PostgreSQL y escucha solo en esta computadora, en `127.0.0.1:8081`. Es una herramienta opcional de administración y requiere iniciar sesión con la cuenta de la base de datos. Para detener solo el panel: `docker compose stop adminer`. Para volver a abrirlo: `bash ver-datos.sh`.

La configuración Compose se validó durante esta revisión. No se pudo iniciar el servicio desde la herramienta de edición: el motor Docker rechazó su conexión al pipe local. El inicio y acceso deben verificarse desde tu terminal Ubuntu.

Referencia de la imagen: https://github.com/docker-library/docs/blob/master/adminer/README.md


## Cargar clientes, proyectos de Huancayo y materiales para una presentación

Con los contenedores del proyecto encendidos, ejecuta en Ubuntu desde esta carpeta:

```bash
bash cargar-ejemplos.sh
```

El script primero guarda un respaldo SQL privado, aplica migraciones pendientes y ejecuta `sistema:cargar-ejemplos`. Atribuye la carga a la primera cuenta administradora activa; si quieres elegir otra, usa `bash cargar-ejemplos.sh --admin=ID`. No crea cuentas de acceso nuevas.

La carga propone 16 clientes con DNI ficticios de 8 dígitos que comienzan con 7, 3 proyectos con 36 lotes disponibles, y 48 materiales de 8 familias con 12 unidades contempladas. Reutiliza las familias y unidades ya existentes, por lo que la cantidad de registros nuevos puede ser menor. Clientes de ejemplo: Valeria Lucía Quispe Huamán (72300267) y Diego Alonso Mendoza Rojas (72887797). No se cargan teléfonos, correos ni domicilios personales inventados.

Proyectos propuestos: Residencial Valle del Mantaro (Pilcomayo), Los Jardines de El Tambo (El Tambo) y Alameda del Sur (Chilca), todos en el contexto de Huancayo, Junín. Cada proyecto tiene manzanas A y B, con 6 lotes por manzana y áreas entre 100 y 160 m². Los precios son referenciales de presentación y no son tasaciones. No se inventan partidas registrales, ventas ni pagos. La ampliación incluye seis reservas de ejemplo con sus clientes y lotes vinculados.

Familias de materiales: construcción, agregados, electricidad, instalaciones sanitarias, pinturas y acabados, herramientas y fijaciones, protección personal, señalización y seguridad. Unidades contempladas: unidad, bolsa, kilogramo, metro, metro cuadrado, metro cúbico, rollo, galón, caja, par, juego y litro. Ejemplos: cemento puzolánico (bolsa), arena gruesa (m³), cable THHN (metro), cerámico (m²) y guantes (par).

Cada material nuevo recibe un SKU generado por su familia, precio unitario, mínimo y una entrada inicial en Kardex; el saldo no se asigna por fuera de Kardex. Algunos materiales se cargan por debajo del mínimo para mostrar el módulo de reposición. Las cantidades son ejemplos y no acreditan existencias físicas de almacén.

La operación es transaccional y se puede repetir sin sumar stock a los materiales ya identificados: compara clientes por DNI, proyectos por nombre y ubicación, lotes por proyecto/manzana/número y productos por nombre, incluidos los archivados. No modifica registros anteriores, precios, unidades, reservas ni saldos existentes. No renombres las claves de los ejemplos si necesitas conservar esta identificación al repetir la carga.

### Ver el resultado

En Adminer selecciona los datos de `clients`, `real_estate_projects`, `land_lots`, `products`, `product_families`, `measurement_units` y `kardex_movements`. Las columnas terminadas en `_id` conectan las tablas; por ejemplo, `products.measurement_unit_id` identifica la unidad de `measurement_units`. En la aplicación abre Clientes, Proyectos y lotes, Productos, Kardex y Reposición. Puedes reservar un lote para uno de los clientes desde su ficha.

El conjunto editable está en `database/data/huancayo.json`: allí puedes adaptar nombres, unidades, cantidades y proyectos para preparar otras presentaciones. Todos los datos son ficticios; los DNI no están verificados en RENIEC y los proyectos no son ofertas verificadas en SUNARP. Los nombres del catálogo no llevan «demo».

### Verificación

Se comprobaron la sintaxis PHP y la consistencia del JSON (DNI únicos, cantidades, unidades y lotes). `tests/Feature/PresentationDataTest.php` cubre relaciones, correspondencia entre saldo y entrada de Kardex, repetición sin duplicados, conservación de datos existentes y ausencia de cambios sin administrador. La ejecución de estas pruebas con la base `testing` está pendiente: el PHP local es 8.0 y la herramienta no tiene acceso al motor Docker. Para ejecutarlas dentro de Docker: `docker compose exec --user sail laravel.test php artisan test --filter=PresentationDataTest`.


## DNI variados, reservas precargadas y navegación de regreso

Para aplicar el diseño y cargar los ejemplos en una sola ejecución, con Docker iniciado y los contenedores del proyecto encendidos:

```bash
bash preparar-presentacion.sh
```

El script reutiliza el respaldo y la actualización de `actualizar.sh`, compila las pantallas y luego carga los datos. Los DNI ahora son números variados de ocho dígitos que comienzan con 7, generados una vez y guardados en el JSON. Si ya cargaste los DNI consecutivos de la versión anterior, se actualizan en el mismo cliente solo cuando coinciden su DNI anterior y sus nombres y apellidos de ejemplo. No se borran clientes ni se modifican nombres de registros existentes. Si otro cliente ocupa el DNI propuesto, se conserva ese registro y no se crean reservas a su nombre si sus datos no coinciden.

Se proponen 6 reservas activas: dos por cada proyecto, sobre los lotes A-01 y A-02, con vigencias de 3, 5, 7, 10, 14 y 15 días desde su primera carga. Se crean con la misma acción que usa el formulario web, con control de disponibilidad, cambio del lote a RESERVADO, ficha histórica de cliente y lote y constancia. Si un lote ya está reservado, vendido o no tiene precio positivo, se omite. Al repetir la carga, las reservas existentes conservan sus fechas y estados, incluso si se cancelaron o vencieron; no se reactivan automáticamente.

Para verlas: Comercial > Reservas > Ver constancia. En la ficha del cliente aparece su historial. En la ficha del lote aparece el acceso a la reserva activa. Los documentos ya existentes conservan los datos que tenían al emitirse, aunque posteriormente se actualice el DNI del cliente.

El botón de regreso se encuentra arriba del contenido de todas las pantallas que usan el diseño principal, excepto Inicio. En los listados dice «Volver al inicio»; dentro de fichas y formularios lleva al apartado o ficha correspondiente. Su destino es interno y funciona aunque abras la página directamente. El menú lateral sigue disponible para cambiar de módulo; en celular se abre desde el botón de menú. Los enlaces anteriores repetidos se retiraron de las fichas para mantener una sola navegación principal de regreso. La impresión de constancias oculta ese botón.

Se revisaron sintaxis PHP, consistencia del JSON y compilación de estilos. Se añadieron pruebas para las seis reservas, repetición sin duplicados, actualización de DNI sin duplicar clientes, conservación de la ficha histórica y destinos de regreso. La ejecución en Laravel/PostgreSQL sigue pendiente por falta de acceso al motor Docker desde la herramienta y por la versión del PHP local.


## Acceso comercial del operador

El operador es un trabajador que participa en el almacén y en la atención comercial. Ve Clientes y Reservas en el menú, consulta todos los clientes y reservas, registra y actualiza clientes, reserva lotes disponibles y obtiene constancias. En la ficha de un lote puede entrar a su reserva activa o reservarlo si está disponible. El código de reserva se genera automáticamente para ambos roles.

| Operación | Administrador | Operador |
| --- | --- | --- |
| Consultar proyectos y lotes | Sí | Sí |
| Consultar, registrar y actualizar clientes | Sí | Sí |
| Consultar y registrar reservas | Sí | Sí |
| Imprimir constancias | Sí | Sí |
| Cancelar reservas indicando motivo | Cualquiera | Solo las que registró |
| Crear proyectos y editar fichas de lotes | Sí | Sin acceso |
| Crear productos y configurar familias, unidades y códigos SKU | Sí | Sin acceso |
| Administrar cuentas y permisos | Sí | Sin acceso |

Para aplicar el cambio: `bash actualizar.sh`. Luego cierra sesión y entra como operador. No necesitas crear otra cuenta ni repetir la carga de ejemplos. Las reservas de ejemplo atribuidas al administrador pueden ser consultadas por el operador; su cancelación corresponde al administrador.

Los permisos se aplican en rutas del servidor y en los botones de la interfaz. La cancelación comprueba el autor de la reserva, sin aceptar un autor enviado desde el formulario. Se conserva el control de disponibilidad y el historial. No se habilitan clientes ni reservas para visitantes sin sesión.

Verificación de esta revisión: sintaxis PHP correcta y ocho casos aislados de las reglas reales de autorización comprobados (administrador, operador, cancelación propia/ajena y rol no autorizado). Se actualizaron las pruebas de `LotReservationTest` para el acceso comercial, edición de clientes, reserva desde el lote, cancelación propia, rechazo de cancelación ajena, administración y acceso anónimo. La ejecución completa con Laravel/PostgreSQL queda pendiente dentro de Docker por las limitaciones del entorno de edición.


## Dashboard flexible y continuación de la reserva a la compra

### Activar esta ampliación

Con Docker y los contenedores encendidos, ejecuta `bash actualizar.sh`. El script guarda un respaldo SQL antes de aplicar la nueva migración de ventas y pagos, compila las pantallas y actualiza las cachés. No es necesario volver a cargar los clientes y lotes de ejemplo.

### Elegir semanas, meses o fechas

En Inicio, dentro del gráfico, elige «Agrupar por»: Semanas o Meses. En «Consultar» puedes seleccionar los últimos periodos (1, 3, 4, 6, 8 o 12) o «Elegir fechas» y completar Desde/Hasta. Pulsa «Actualizar resumen». El enlace «Ver últimas 4 semanas» ofrece una consulta rápida.

Las semanas comienzan el lunes y las fechas se interpretan en hora de Perú. El rango personalizado admite hasta 366 días incluidos sus extremos y no permite fechas finales futuras. Las barras cuentan movimientos de Kardex; al pulsarlas se abre su historial con las mismas fechas locales. El rango actual termina en el día de hoy, por lo que la última semana o mes puede estar incompleto.

El dashboard muestra también clientes registrados, reservas activas, ventas del periodo, cobros según su fecha de pago y saldo pendiente. Clientes, reservas activas, existencias y saldo pendiente son datos actuales; las ventas y los cobros corresponden al periodo seleccionado. Soles y dólares se muestran por separado, sin conversión ni suma entre monedas.

### Después de reservar: registrar la compra

1. Como administrador, abre Comercial > Reservas y entra a una reserva activa y vigente.
2. Pulsa «Continuar con la venta». Revisa el comprador, el lote y el precio total acordado; agrega observaciones si hace falta.
3. Marca la confirmación y pulsa «Confirmar venta». Se crea un código VEN, la reserva queda «Convertida en venta» y el lote queda «Vendido» dentro del catálogo comercial. La operación todavía tiene saldo pendiente: confirmar una venta no registra dinero recibido.
4. Desde Ventas y pagos abre la compra. Registra cada abono recibido: importe en la moneda de la venta, fecha, método y referencia. Para transferencia y depósito se exige número de operación/referencia. No se admiten pagos anteriores a la fecha de venta ni posteriores al día actual.
5. El saldo baja con cada pago y, al cubrir el total, la venta queda «Pagada». Puedes imprimir el resumen y consultar el historial con su responsable. Si el historial está paginado, el resumen indica qué página se imprime.

Ejemplo: precio total acordado S/ 45,000; primer abono S/ 5,000; saldo S/ 40,000. Tras otro abono de S/ 40,000, el saldo queda en cero y el estado pasa a Pagada. No se puede registrar un abono que exceda el saldo.

El precio y la moneda de la venta se toman de la reserva; el formulario no permite reemplazarlos. Cada reserva y lote puede originar una sola venta. Reenviar la confirmación no crea otra venta. Los pagos usan un identificador de operación para evitar duplicados al reenviar el formulario y comprueban referencias repetidas dentro de la misma venta y método. Los importes se calculan en céntimos y se guardan como decimales.

Una reserva convertida no se cancela ni vence como reserva activa, y el lote vendido no admite cambios manuales de ficha que contradigan la venta. No se implementó anulación de ventas, devolución de pagos, intereses ni conexión bancaria. El cronograma de cuotas se añadió en la ampliación de financiamiento descrita más abajo. Los abonos representan pagos registrados por el personal, no una verificación automática del banco.

El operador puede consultar ventas, historial, saldo y resúmenes. El administrador confirma compras y registra cobros. El historial del cliente enlaza a sus ventas y la ficha del lote enlaza a la operación correspondiente. Los permisos anteriores de almacén se mantienen.

Los documentos de esta ampliación son registros internos: no acreditan transferencia registral, formalización notarial, emisión de comprobantes SUNAT ni validación con SUNARP. Esos procesos siguen pendientes de una integración o flujo específico.

### Tablas y verificación

Las nuevas tablas son `lot_sales` y `lot_payments`. La primera enlaza reserva, lote, cliente y acuerdo; la segunda guarda los abonos, su fecha, método, referencia y responsable. Las claves foráneas conservan los vínculos y las restricciones únicas impiden duplicar ventas para el mismo lote o reserva.

Se revisó sintaxis PHP y compilación CSS. Se comprobaron de forma aislada cálculos exactos de céntimos y límites de fechas diarias en Perú. Se añadieron `LotSaleTest` y `DashboardPeriodsTest`: conversión única, reserva vencida, pagos parciales, reenvíos, sobrepago, referencias, permisos, semanas y rangos y correspondencia con Kardex. La ejecución completa con Laravel/PostgreSQL está pendiente porque el PHP local es 8.0.30 y el proyecto requiere al menos 8.4.1, y la herramienta de edición no puede acceder al motor Docker.

Dentro de Docker y con la base de pruebas configurada, se pueden ejecutar con `docker compose exec --user sail laravel.test php artisan test --filter='LotSaleTest|DashboardPeriodsTest'`.


Revisión visual adicional de esta ampliación: la vista de ventas y pagos se comprobó con datos ficticios a 320, 390 y 1440 px, sin desbordamiento de la página, y en impresión con el formulario oculto. El selector del dashboard se comprobó con Alpine: alterna entre periodos recientes y fechas personalizadas, habilita y exige las fechas al elegir el rango y funciona en pantalla móvil. Estas comprobaciones no sustituyen las pruebas completas contra PostgreSQL.


## Contado, inicial y cuotas mensuales

### Dónde verlo

1. Inicia los contenedores y ejecuta `bash actualizar.sh`. El script respalda la base y aplica la migración de planes, cronogramas y distribución de pagos; no necesitas repetir la carga de datos de presentación.
2. En Comercial > **Planes de pago**, consulta las propuestas de 6, 12, 24 y 48 meses. La configuración inicial es 10% de inicial mínima y saldo sin intereses. El administrador puede cambiar nombre, meses (de 1 a 120), porcentaje mínimo o desactivar un plan para ventas nuevas. También puede crear otro plan.
3. Desde una reserva activa, entra a Continuar con la venta / Simular contado o cuotas. Selecciona **Al contado** o **Inicial + cuotas mensuales**. Para cuotas, elige plan, inicial acordada y primera fecha de pago mensual. La inicial puede ser mayor al mínimo.
4. El simulador presenta cada vencimiento e importe antes de guardar. El administrador revisa y confirma la venta y su cronograma; el operador puede mostrar la simulación al comprador sin confirmar la operación.
5. En Ventas y pagos, cada compra muestra modalidad, cronograma, pago aplicado, saldo y estado de cada vencimiento. Registra los pagos recibidos desde esa ficha.

Ejemplo en soles: precio S/ 45,000; inicial 10% = S/ 4,500; saldo S/ 40,500. A 24 meses, las cuotas resultan S/ 1,687.50. Con ese mismo saldo, a 12 meses resultan S/ 3,375 y a 48 meses S/ 843.75. La elección se hace antes de confirmar. El precio acordado y la moneda provienen de la reserva y no se reemplazan por otro precio al seleccionar un plan.

La estructura de referencia se tomó de una promoción histórica publicada por Menorca para San Antonio de Mala, etapa 4: inicial mínima de 10% y hasta 48 meses sin intereses. La publicación indicaba contratos del 10/04/2026 al 30/06/2026. **No se presenta como oferta vigente ni como política oficial de Leidy.** Los plazos de 6, 12 y 24 meses son propuestas del proyecto, configurables por administración. No se copiaron descuentos, tipos de cambio ni precios de aquella promoción. Fuente: https://menorca.pe/promocion/promocion-mala-hasta-48-meses-sin-intereses/

### Cómo se aplican los pagos

La inicial vence en la fecha de la venta. Cada cuota mensual parte de la fecha elegida; cuando el mes no tiene el día 29, 30 o 31, se usa su último día. Se conserva el día original como referencia para meses posteriores. Los importes se calculan en céntimos, la última cuota ajusta el residuo y la suma de inicial y cuotas coincide exactamente con el precio total.

Cada abono cubre primero la inicial y después las cuotas por número. Se admiten abonos parciales y adelantos. El historial muestra a qué vencimientos se aplicó cada pago. Reenviar un pago no vuelve a repartirlo; la operación de pago, aplicación y actualización del saldo es transaccional. El pago no puede superar el saldo general.

Los estados son calculados al consultar: Pagada, Pendiente, Pago parcial o Vencida. Un vencimiento se considera atrasado a partir del día siguiente a su fecha, en hora de Perú; no se generan moras, penalidades ni intereses automáticamente.

Los planes cambiados o desactivados no recalculan contratos existentes: cada venta conserva una copia de sus condiciones y sus importes. No se permite sobrescribir un cronograma ya registrado.

### Ventas que ya tenían abonos

Las ventas anteriores conservan su modalidad sin definir y sus pagos originales. Desde su ficha, el administrador puede seleccionar **Definir contado o cuotas**. Al guardar el nuevo acuerdo, los abonos existentes se distribuyen sobre el cronograma sin crear cobros nuevos ni cambiar el total pagado. La primera cuota mensual de un nuevo acuerdo a plazos debe vencer después del día actual; la inicial conserva la fecha de la venta. Si la suma del historial no coincide con el total pagado, el sistema rechaza la configuración para que se revise la venta.

### Dashboard unificado

El panel utiliza una sola paleta azul y gris, encabezado y tarjetas con tipografía y espaciados consistentes, filtros de periodo en una sola sección e indicadores de tamaño uniforme. Incluye actividad del almacén, pendientes de atención, saldo por cobrar, cuotas vencidas, próximos vencimientos de hoy y los siguientes 7 días y reposición. Las alertas se limitan a ventas con cronograma; las ventas anteriores sin fechas no generan vencimientos inventados. Soles y dólares continúan separados. El acceso desde una alerta de vencidos filtra las ventas correspondientes.

### Alcance y verificación

Las nuevas tablas son `financing_plans`, `sale_installments` y `payment_allocations`. La venta guarda modalidad, plan y condiciones de financiamiento. Se mantienen las restricciones de permisos: operador consulta y simula; administrador configura planes, confirma ventas y registra pagos.

Estos planes son financiamiento directo simple sin intereses. No hay TEA/TCEA con intereses, evaluación crediticia, reprogramación de cronogramas confirmados, refinanciación, moras, devoluciones, anulaciones ni conexión bancaria. Los documentos siguen siendo registros internos, sin certificación notarial, SUNAT o SUNARP.

Verificación realizada: sintaxis PHP/JavaScript y compilación CSS; cálculos aislados de inicial mínima redondeada al céntimo, reparto exacto, límites y cuota final; simulador en navegador con inicial, contado, 24 cuotas, céntimos, fin de mes y año bisiesto; vistas del dashboard a 320, 390 y 1440 px, sin desbordamiento horizontal. Las vistas de revisión usan datos ficticios. `FinancingScheduleTest` cubre cronograma, pagos parciales y reenvíos, distribución de abonos anteriores, rechazo de condiciones inválidas, plan inactivo y permisos. Las pruebas integradas siguen pendientes dentro de Docker: el PHP disponible en la herramienta es 8.0.30 y el proyecto requiere al menos 8.4.1. Comando dentro del contenedor con la base de pruebas configurada: `php artisan test --filter=FinancingScheduleTest`.


## Cobro asistido y adelanto de cuotas

Activa la ampliación con `bash actualizar.sh`, con los contenedores encendidos. Se aplica una migración pequeña que identifica el tipo de operación del pago; los pagos anteriores se conservan como Abono libre. No se vuelve a cargar el catálogo ni se duplican clientes.

### Dónde cobrar

Desde Comercial > Clientes, abre la ficha y elige la compra con saldo pendiente; pulsa «Pagar o adelantar». También puedes abrir Ventas y pagos > Ver compra y pagos. Cada compra mantiene su propio cronograma y moneda, incluso si el mismo cliente tiene varios lotes. El administrador registra el cobro; el operador consulta cuotas, pagos y saldos.

El panel ofrece cuatro opciones:

- **Pagar el siguiente vencimiento:** propone el saldo completo de la inicial, pago único o cuota pendiente más antigua. Después de cubrir la inicial, el siguiente ingreso a la ficha propone la primera cuota pendiente.
- **Adelantar o pagar varias cuotas:** selecciona cuántas cuotas mensuales pendientes cubrir. Se calcula el importe de sus saldos reales, incluyendo cuotas parcialmente pagadas. Si queda inicial pendiente, se incluye antes de las cuotas y se indica expresamente en el resumen.
- **Cancelar todo el saldo:** propone el saldo total actual de esa venta.
- **Otro importe / abono parcial:** permite escribir una cantidad libre dentro del saldo. El resumen indica qué parte de cada vencimiento se cubrirá.

La fecha viene completada con el día actual en Perú. Es la fecha del dinero recibido; no se reemplaza por un vencimiento futuro al adelantar cuotas. Puede ajustarse para registrar un pago anterior dentro de las fechas permitidas.

Ejemplo: con la inicial ya pagada y cuotas de S/ 1,687.50, elegir tres cuotas propone S/ 5,062.50. Si queda un saldo parcial en la primera cuota, el cálculo toma solo lo pendiente de esa cuota y suma las siguientes. En el cronograma, «Cubrir hasta aquí» prepara el pago de los vencimientos pendientes hasta esa fila; no omite cuotas anteriores.

Antes de enviar, se presenta una revisión con comprador, venta, fecha, método, importe, distribución y saldo final. El número de operación se exige para transferencia o depósito. El botón se bloquea durante el envío y el identificador del formulario evita registrar de nuevo un pago reenviado.

### Consistencia y concurrencia

El servidor bloquea la fila de la venta y vuelve a calcular las opciones automáticas a partir de su cronograma vigente. Compara el importe y saldo que revisó el usuario: si otro usuario registró un pago mientras el formulario estaba abierto, se solicita recargar antes de confirmar. Ese control también se aplica al abono libre cuando se envía el saldo revisado. Los pagos idempotentes ya existentes se devuelven antes de esa comprobación, de modo que un reenvío no se convierte en un segundo cobro.

Los abonos se aplican primero a la inicial y luego a las cuotas por número. El adelanto no cambia precios ni vencimientos ni genera recargos. El pago, sus aplicaciones y la actualización del saldo se guardan en una sola transacción. En el historial queda la operación (siguiente vencimiento, varias cuotas, saldo o libre), la cantidad de cuotas elegida y los importes realmente aplicados.

Las ventas antiguas sin cronograma admiten abono libre o cancelación de saldo; administración puede definir su cronograma antes de usar opciones por cuota. No se inventa una cuota para una venta que no tiene condiciones registradas.

### Verificación

Sintaxis PHP/JavaScript y compilación CSS comprobadas. Se ejecutaron comprobaciones aisladas del cálculo de siguiente vencimiento, inicial incluida, cuotas parciales, adelantos y cantidades inválidas. El formulario se verificó en navegador con datos ficticios: importe y fecha sugeridos, inicial pendiente, adelanto tras pagar inicial, cancelación de saldo, abono libre, referencia bancaria obligatoria, revisión final, contenido del envío y bloqueo del botón; vistas a 320, 390 y 1440 px sin desbordamiento horizontal.

`PaymentAssistanceTest` cubre sugerencias por venta, pago idempotente, adelantos, conservación de fechas, independencia entre clientes/ventas, importes alterados, cantidades inválidas, saldos desactualizados y cancelación del saldo. La ejecución integrada queda pendiente en Docker: el PHP local disponible es 8.0.30 y la aplicación requiere al menos 8.4.1. Dentro del contenedor, con la base de pruebas configurada: `php artisan test --filter=PaymentAssistanceTest`.
