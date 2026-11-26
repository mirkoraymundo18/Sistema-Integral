<x-app-layout>
    <section class="catalog" aria-labelledby="users-title">
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Accesos</p>
                <h1 id="users-title">Usuarios y permisos</h1>
                <p>Administra las cuentas del personal y consulta el alcance de cada rol.</p>
            </div>
            <a class="catalog-button catalog-primary" href="{{ route('users.create') }}">+ Nueva cuenta</a>
        </header>

        @if (session('success'))
            <p class="catalog-notice" role="status">{{ session('success') }}</p>
        @endif

        <nav class="catalog-stats lg:grid-cols-3" aria-label="Resumen de cuentas">
            <a class="catalog-stat {{ $status === 'activo' && ($filters['role'] ?? null) === 'ADMINISTRADOR' ? 'ring-2 ring-blue-600' : '' }}"
                href="{{ route('users.index', ['role' => 'ADMINISTRADOR']) }}">
                <span>Administradores activos</span><strong>{{ $activeCounts['ADMINISTRADOR'] ?? 0 }}</strong>
            </a>
            <a class="catalog-stat {{ $status === 'activo' && ($filters['role'] ?? null) === 'OPERADOR' ? 'ring-2 ring-blue-600' : '' }}"
                href="{{ route('users.index', ['role' => 'OPERADOR']) }}">
                <span>Operadores activos</span><strong>{{ $activeCounts['OPERADOR'] ?? 0 }}</strong>
            </a>
            <a class="catalog-stat {{ $status === 'desactivado' ? 'ring-2 ring-blue-600' : '' }}"
                href="{{ route('users.index', ['status' => 'desactivado']) }}">
                <span>Cuentas desactivadas</span><strong>{{ $inactiveCount }}</strong>
            </a>
        </nav>

        <section class="catalog-panel" aria-labelledby="users-directory">
            <div class="catalog-body catalog-heading">
                <div>
                    <h2 id="users-directory">{{ $status === 'desactivado' ? 'Cuentas desactivadas' : 'Cuentas activas' }}</h2>
                    <p>{{ $users->total() }} {{ $users->total() === 1 ? 'cuenta' : 'cuentas' }} en la consulta</p>
                </div>
                <a class="catalog-link" href="#roles-del-almacen">Ver permisos por rol ↓</a>
            </div>
            @if ($status === 'desactivado')
                <div class="mx-4 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                    <p>Esta vista reúne las cuentas desactivadas anteriormente.</p>
                    <a class="catalog-link" href="{{ route('users.index', ['status' => 'activo']) }}">Ver cuentas activas</a>
                </div>
            @endif
            <form method="GET" class="catalog-filters" action="{{ route('users.index') }}">
                <label>Buscar cuenta
                    <input type="search" name="search" maxlength="255" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre, correo o DNI completo">
                </label>
                <label>Rol
                    <select name="role">
                        <option value="">Todos los roles</option>
                        <option value="ADMINISTRADOR" @selected(($filters['role'] ?? null) === 'ADMINISTRADOR')>Administrador</option>
                        <option value="OPERADOR" @selected(($filters['role'] ?? null) === 'OPERADOR')>Operador</option>
                    </select>
                </label>
                <label>Estado
                    <select name="status">
                        <option value="activo" @selected($status === 'activo')>Activas</option>
                        <option value="desactivado" @selected($status === 'desactivado')>Desactivadas</option>
                    </select>
                </label>
                <button type="submit" class="catalog-button">Filtrar</button>
                @if (array_filter($filters))
                    <a class="catalog-link" href="{{ route('users.index') }}">Limpiar</a>
                @endif
            </form>

            @if ($users->isEmpty())
                <div class="catalog-empty">
                    <h2>Sin cuentas para esta consulta</h2>
                    <p>Cambia la búsqueda o los filtros para ver otras cuentas.</p>
                </div>
            @else
                <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
                    <table class="catalog-table" aria-label="Cuentas del almacén">
                        <thead><tr><th scope="col">Cuenta</th><th scope="col">Rol</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        @if ($user->id === auth()->id())
                                            <span class="catalog-badge bg-blue-50 text-blue-800">Tu cuenta</span>
                                        @endif
                                        <small>{{ $user->email }}</small>
                                    </td>
                                    <td><span class="catalog-badge {{ $user->role === 'ADMINISTRADOR' ? 'bg-blue-50 text-blue-800' : 'bg-slate-100 text-slate-700' }}">{{ ucfirst(strtolower($user->role)) }}</span></td>
                                    <td>
                                        @if ($user->trashed())
                                            <span class="catalog-badge catalog-danger">Desactivada</span>
                                            <small>{{ $user->deleted_at->format('d/m/Y') }}</small>
                                        @else
                                            <span class="catalog-badge catalog-good">Activa</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!$user->trashed())
                                            <a class="catalog-link whitespace-nowrap" href="{{ route('users.edit', $user) }}" aria-label="Editar cuenta de {{ $user->name }}">Editar cuenta</a>
                                        @else
                                            <details><summary class="catalog-link cursor-pointer">Reactivar cuenta</summary><form method="POST" action="{{ route('users.restore',$user) }}" class="mt-3 grid gap-2">@csrf<p class="text-xs text-slate-500">Recuperará el acceso con su rol y contraseña anteriores. El historial se conserva.</p><button type="submit" class="catalog-button">Confirmar reactivación</button></form></details>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="catalog-pagination">{{ $users->links() }}</div>
                @endif
            @endif
        </section>

        <details id="roles-del-almacen" class="catalog-panel" open>
            <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-900">Qué puede hacer cada rol</summary>
            <p class="px-5 pb-2 pt-3 text-xs text-slate-500 sm:hidden">Desliza la tabla para ver todas las columnas.</p>
<div class="catalog-table-wrap">
                <table class="catalog-table" aria-label="Matriz de permisos del almacén">
                    <thead><tr><th scope="col">Acción</th><th scope="col">Administrador</th><th scope="col">Operador</th></tr></thead>
                    <tbody>
                        <tr><td>Consultar catálogo y registrar movimientos en Kardex</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Consultar y exportar historial de Kardex</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Crear solicitudes y consultar su estado</td><td>Todas las solicitudes</td><td>Sus propias solicitudes</td></tr>
                        <tr><td>Gestionar productos, familias y unidades</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Aprobar, rechazar y despachar solicitudes</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Confirmar conteos físicos y ajustes de inventario</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Consultar y exportar reposición</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Consultar proyectos y lotes</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Registrar proyectos y modificar fichas de lotes</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Consultar, registrar y actualizar clientes</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Consultar y registrar reservas; imprimir constancias</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Consultar ventas, pagos y saldos</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Consultar planes y cronogramas de cuotas</td><td>Sí</td><td>Sí</td></tr>
                        <tr><td>Configurar planes y condiciones de financiamiento</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Confirmar ventas y registrar cobros</td><td>Sí</td><td>Sin acceso</td></tr>
                        <tr><td>Cancelar reservas con motivo</td><td>Cualquier reserva</td><td>Solo las que registró</td></tr>
                        <tr><td>Crear, editar y desactivar cuentas</td><td>Sí</td><td>Sin acceso</td></tr>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
</x-app-layout>
