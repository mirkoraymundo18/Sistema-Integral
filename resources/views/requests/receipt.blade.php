<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entrega #{{ $order->id }} · Leidy Inmobiliaria</title>
<style>
body{font:16px/1.6 system-ui,sans-serif;color:#172238;background:#f3f5f8;margin:0;padding:24px}
main{max-width:800px;margin:auto;background:white;padding:32px;border:1px solid #dce2ea}
h1{font-size:26px;line-height:1.2}dt{font-weight:600}dd{margin:0 0 16px}button,a{font:inherit}
.actions{display:flex;gap:16px;margin-bottom:24px}.signatures{display:flex;justify-content:space-between;gap:40px;margin-top:70px}.signatures p{border-top:1px solid #777;flex:1;text-align:center}
@media print{body{background:white;padding:0}.actions{display:none}main{border:0;padding:0}}
</style>
</head>
<body>
<main>
<div class="actions">
<button type="button" onclick="window.print()">Imprimir o guardar PDF</button>
<a href="{{ route('requests.index', ['estado' => 'Entregado']) }}">Volver</a>
</div>
<p>Leidy Inmobiliaria · Almacén Central</p>
<h1>Comprobante de entrega #{{ $order->id }}</h1>
<dl>
<dt>Fecha de entrega</dt>
<dd>{{ $order->movement->created_at->format('d/m/Y H:i') }}</dd>
<dt>Solicitante</dt>
<dd>{{ $order->user?->name }}</dd>
<dt>Producto</dt>
<dd>{{ $order->product?->codigo_sku }} · {{ $order->product?->nombre }}</dd>
<dt>Cantidad entregada</dt>
<dd>{{ $order->movement->cantidad }} {{ $order->product?->unit?->simbolo ?? 'und.' }}</dd>
<dt>Destino o motivo</dt>
<dd>{{ $order->motivo }}</dd>
<dt>Responsable de entrega</dt>
<dd>{{ $order->movement->user?->name }}</dd>
<dt>Registro de Kardex</dt>
<dd>#{{ $order->kardex_movement_id }}</dd>
</dl>
<div class="signatures">
<p>Entregó</p>
<p>Recibió</p>
</div>
</main>
</body>
</html>
