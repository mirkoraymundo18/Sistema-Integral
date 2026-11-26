<x-app-layout>
    @php($ownAccount = $user->exists && $user->id === auth()->id())
    @php($role = $ownAccount ? $user->role : old('role', $user->role ?? 'OPERADOR'))
    <section class="catalog catalog-narrow" aria-labelledby="user-form-title">
        
        <header class="kardex-heading">
            <div>
                <p class="catalog-eyebrow">Almacén central / Accesos</p>
                <h1 id="user-form-title">{{ $user->exists ? 'Editar cuenta' : 'Nueva cuenta' }}</h1>
                <p>{{ $user->exists ? $user->name : 'Configura el acceso de una persona al almacén.' }}</p>
            </div>
        </header>

        <form class="catalog-panel catalog-form" method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists)
                @method('PATCH')
            @endif
            <div class="catalog-fields">
                <label>Nombre completo
                    <input name="name" autocomplete="off" required maxlength="255" value="{{ old('name', $user->name) }}">
                </label>
                <label>Correo electrónico
                    <input type="email" name="email" autocomplete="off" required maxlength="255" value="{{ old('email', $user->email) }}">
                </label>
            </div>
            <label>DNI <small>(opcional)</small>
                <input name="dni" type="text" inputmode="numeric" minlength="8" maxlength="8" pattern="[0-9]{8}" autocomplete="off" value="{{ old('dni', $user->dni) }}" aria-describedby="dni-help">
            </label>
            <p id="dni-help" class="catalog-help">Registra 8 dígitos para habilitar el acceso con DNI y contraseña. El correo también permite ingresar.</p>
            @if (!$user->exists)
                <label>Contraseña inicial
                    <input type="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="new-user-password-help">
                </label>
                <p id="new-user-password-help" class="catalog-help">Usa al menos 8 caracteres.</p>
            @endif

            @if ($ownAccount)
                <label>Rol de tu cuenta
                    <input readonly value="Administrador">
                    <input type="hidden" name="role" value="{{ $role }}">
                </label>
                <p class="catalog-help">Tu rol se conserva para mantener el acceso administrativo.</p>
            @else
                <label>Rol
                    <select id="user-role" name="role" required aria-describedby="user-role-help">
                        <option value="OPERADOR" @selected($role === 'OPERADOR')>Operador</option>
                        <option value="ADMINISTRADOR" @selected($role === 'ADMINISTRADOR')>Administrador</option>
                    </select>
                </label>
                <p id="user-role-help" class="catalog-help" role="status"
                    data-operador="Consulta el catálogo, registra movimientos y envía sus propias solicitudes."
                    data-administrador="Gestiona el catálogo, las entregas y las cuentas del almacén.">
                    {{ $role === 'ADMINISTRADOR'
                        ? 'Gestiona el catálogo, las entregas y las cuentas del almacén.'
                        : 'Consulta el catálogo, registra movimientos y envía sus propias solicitudes.' }}
                </p>
            @endif

            <div class="catalog-actions">
                <button class="catalog-button catalog-primary" type="submit">{{ $user->exists ? 'Guardar cambios' : 'Crear cuenta' }}</button>
                <a class="catalog-button" href="{{ route('users.index') }}">Cancelar</a>
            </div>
        </form>

        @if ($user->exists && !$ownAccount)
            <details class="catalog-panel">
                <summary class="cursor-pointer px-5 py-4 font-semibold text-red-800">Desactivar acceso</summary>
                <form class="catalog-form border-t border-slate-200" method="POST" action="{{ route('users.destroy', $user) }}">
                    @csrf
                    @method('DELETE')
                    <p class="catalog-help">{{ $user->name }} perderá el acceso. Sus solicitudes y movimientos seguirán en el historial.</p>
                    <div><button class="catalog-button catalog-danger" type="submit">Confirmar desactivación</button></div>
                </form>
            </details>
        @endif
    </section>

    <script>
        (() => {
            const role = document.getElementById('user-role');
            if (!role) return;
            const help = document.getElementById('user-role-help');
            role.addEventListener('change', () => {
                help.textContent = help.dataset[role.value.toLowerCase()];
            });
        })();
    </script>
</x-app-layout>
