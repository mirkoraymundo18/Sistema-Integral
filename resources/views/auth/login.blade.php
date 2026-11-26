<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-slate-600">Accede al Almacén Central con tu DNI o correo y tu contraseña.</p>
    </div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'DNI o correo electrónico'" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" maxlength="255" placeholder="DNI de 8 dígitos o correo" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="'Contraseña'" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <p class="mt-4 text-sm text-slate-600">La sesión vence tras 30 minutos de inactividad.</p>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <button type="submit" class="catalog-button catalog-primary ms-3">
                Ingresar
            </button>
        </div>
    </form>
</x-guest-layout>
