<x-app-layout>
    <section class="catalog catalog-narrow">
        <header class="kardex-heading"><div><p class="catalog-eyebrow">Mi cuenta / Preferencias</p><h1>Mi perfil</h1><p>Actualiza tus datos de contacto y tu contraseña.</p></div></header>
        <div class="catalog-panel catalog-form">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="catalog-panel catalog-form">
            @include('profile.partials.update-password-form')
        </div>
        @cannot('administrar-almacen')
            <div class="catalog-panel catalog-form">
                @include('profile.partials.delete-user-form')
            </div>
        @endcannot
    </section>
</x-app-layout>
