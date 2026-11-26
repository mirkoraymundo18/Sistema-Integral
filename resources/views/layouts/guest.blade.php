<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Leidy Inmobiliaria') }} · Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="erp-auth font-sans antialiased">
    <main class="erp-auth-layout">
        <section class="erp-auth-intro" aria-label="Leidy Inmobiliaria">
            <a class="erp-brand" href="{{ route('login') }}"><span class="erp-brand-symbol"><x-erp-icon name="building" /></span><span><strong>Leidy Inmobiliaria</strong><small>Gestión de almacén</small></span></a>
            <div class="erp-auth-message">
                <p class="erp-auth-eyebrow">Almacén Central</p>
                <h2>El control de tu almacén,<br>en un solo lugar.</h2>
                <p>Organiza materiales, consulta existencias y sigue cada movimiento de tu equipo.</p>
                <div class="erp-auth-features"><span>Inventario</span><span>Kardex</span><span>Requerimientos</span></div>
            </div>
            <p class="erp-auth-caption">Gestión interna · Leidy Inmobiliaria</p>
        </section>
        <section class="erp-auth-form">
            <div class="erp-auth-card">{{ $slot }}</div>
            <p class="erp-auth-help">Almacén Central · Acceso del personal autorizado</p>
        </section>
    </main>
</body>
</html>
