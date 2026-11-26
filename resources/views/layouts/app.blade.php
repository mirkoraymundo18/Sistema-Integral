<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Leidy Inmobiliaria') }} · {{ request()->routeIs('dashboard') ? 'Gestión integral' : (request()->routeIs('commercial.*') ? 'Gestión comercial' : 'Almacén Central') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $navigation = [
        ['route' => 'dashboard', 'label' => 'Resumen general', 'icon' => 'dashboard', 'group' => 'Inicio', 'active' => request()->routeIs('dashboard')],
        ['route' => 'products.index', 'label' => 'Productos', 'icon' => 'products', 'group' => 'Operaciones', 'active' => request()->routeIs('products.index', 'products.create', 'products.show', 'products.edit')],
        ['route' => 'kardex.index', 'label' => 'Kardex', 'icon' => 'kardex', 'group' => 'Operaciones', 'active' => request()->routeIs('kardex.*')],
        ['route' => 'requests.index', 'label' => 'Requerimientos', 'icon' => 'requests', 'group' => 'Operaciones', 'active' => request()->routeIs('requests.*')],
        ['route' => 'restock.index', 'label' => 'Reposición', 'icon' => 'restock', 'group' => 'Operaciones', 'active' => request()->routeIs('restock.*')],
    ];
    $navigation[] = ['route' => 'commercial.projects.index', 'label' => 'Proyectos y lotes', 'icon' => 'building', 'group' => 'Comercial', 'active' => request()->routeIs('commercial.projects.*', 'commercial.lots.*')];
    if (auth()->user()->can('gestionar-comercial')) {
        $navigation = array_merge($navigation, [
            ['route' => 'commercial.clients.index', 'label' => 'Clientes', 'icon' => 'users', 'group' => 'Comercial', 'active' => request()->routeIs('commercial.clients.*')],
            ['route' => 'commercial.plans.index', 'label' => 'Planes de pago', 'icon' => 'settings', 'group' => 'Comercial', 'active' => request()->routeIs('commercial.plans.*')],
            ['route' => 'commercial.sales.index', 'label' => 'Cobros y deudas', 'icon' => 'requests', 'group' => 'Comercial', 'active' => request()->routeIs('commercial.sales.*','commercial.payments.*')],
            ['route' => 'commercial.reservations.index', 'label' => 'Reservas', 'icon' => 'requests', 'group' => 'Comercial', 'active' => request()->routeIs('commercial.reservations.*')],
        ]);
    }
    if (auth()->user()->can('administrar-almacen')) {
        $navigation = array_merge($navigation, [
            ['route' => 'counts.index', 'label' => 'Conteo físico', 'icon' => 'counts', 'group' => 'Control', 'active' => request()->routeIs('counts.*')],
            ['route' => 'products.settings', 'label' => 'Familias y unidades', 'icon' => 'settings', 'group' => 'Control', 'active' => request()->routeIs('products.settings', 'products.families.*', 'products.units.*')],
            ['route' => 'users.index', 'label' => 'Usuarios y permisos', 'icon' => 'users', 'group' => 'Control', 'active' => request()->routeIs('users.*')],
        ]);
    }
    $navigation[] = ['route'=>'help.index', 'label'=>'Guía de uso', 'icon'=>'requests', 'group'=>'Ayuda', 'active'=>request()->routeIs('help.*','system.*')];
    $currentPage = collect($navigation)->firstWhere('active', true)['label'] ?? 'Mi cuenta';
@endphp
<body class="erp-app font-sans antialiased" x-data="{ menuOpen: false, desktop: window.innerWidth >= 1024 }"
    @resize.window.debounce.150ms="desktop = window.innerWidth >= 1024; if (desktop) menuOpen = false"
    @keydown.escape.window="menuOpen = false"  :class="{ 'overflow-hidden': menuOpen }">
    <a href="#contenido-principal" class="erp-skip">Ir al contenido</a>
    <button type="button" class="erp-overlay" x-cloak x-show="menuOpen" @click="menuOpen = false" aria-label="Cerrar navegación"></button>

    <aside id="erp-navigation" class="erp-sidebar" :class="{ 'erp-sidebar-open': menuOpen }" :inert="!desktop && !menuOpen" aria-label="Navegación principal">
        <a class="erp-brand" href="{{ route('dashboard') }}">
            <span class="erp-brand-symbol"><x-erp-icon name="building" /></span>
            <span><strong>Leidy Inmobiliaria</strong><small>Gestión integral</small></span>
        </a>
        <button class="erp-mobile-close" type="button" @click="menuOpen = false" aria-label="Cerrar menú"><x-erp-icon name="close" /></button>

        <nav class="erp-navigation">
            @foreach (collect($navigation)->groupBy('group') as $group => $items)
                <div class="erp-nav-group">
                    <p class="erp-nav-label">{{ $group }}</p>
                    @foreach ($items as $item)
                        <a class="erp-nav-link {{ $item['active'] ? 'is-active' : '' }}"
                            href="{{ route($item['route']) }}" @if($item['active']) aria-current="page" @endif>
                            <x-erp-icon :name="$item['icon']" />
                            <span>{{ $item['label'] }}</span>
                            @if($item['active'])<span class="erp-nav-dot" aria-hidden="true"></span>@endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="erp-sidebar-footer">
            <a class="erp-account" href="{{ route('profile.edit') }}">
                <span class="erp-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="min-w-0"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'ADMINISTRADOR' ? 'Administrador' : 'Operador' }}</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="erp-logout"><x-erp-icon name="logout" /><span>Cerrar sesión</span></button>
            </form>
        </div>
    </aside>

    <div class="erp-workspace">
        <header class="erp-topbar">
            <div class="erp-topbar-location">
                <button type="button" class="erp-mobile-toggle" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="erp-navigation" aria-label="Abrir menú"><x-erp-icon name="menu" /></button>
                <span class="erp-topbar-parent">{{ request()->routeIs('dashboard') ? 'Gestión integral' : (request()->routeIs('commercial.*') ? 'Comercial' : 'Almacén') }}</span><span class="erp-separator">/</span><strong>{{ $currentPage }}</strong>
            </div>
            <span class="erp-location"><span aria-hidden="true"></span>{{ request()->routeIs('dashboard') ? 'Gestión integral' : (request()->routeIs('commercial.*') ? 'Gestión comercial' : 'Almacén Central') }}</span>
        </header>

        <main id="contenido-principal" class="erp-content">
            <x-back-navigation />
            @if (isset($header))
                <header class="erp-legacy-header">{{ $header }}</header>
            @endif
            @if ($errors->any())
                <div role="alert" class="erp-form-alert">
                    <strong>Revisa los datos para continuar</strong>
                    <ul>
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            {{ $slot }}
            <footer class="erp-page-footer">Leidy Inmobiliaria <span>{{ request()->routeIs('dashboard') ? 'Gestión integral' : (request()->routeIs('commercial.*') ? 'Gestión comercial' : 'Almacén Central') }}</span></footer>
        </main>
    </div>
</body>
</html>
