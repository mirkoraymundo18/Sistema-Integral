<x-app-layout>
<section class="catalog catalog-narrow">
    
    <header class="kardex-heading"><div><p class="catalog-eyebrow">Gestión comercial / Contactos</p><h1>{{ $client->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1><p>Ingresa los nombres y apellidos que figuran en su documento.</p></div></header>
    <form class="catalog-panel catalog-form" method="POST" action="{{ $client->exists ? route('commercial.clients.update', $client) : route('commercial.clients.store') }}">
        @csrf
        @if($client->exists) @method('PATCH') @endif
        <p class="catalog-help">Origen de los datos · Registro manual del personal.</p>
        <label>DNI<input type="text" name="dni" inputmode="numeric" minlength="8" maxlength="8" pattern="[0-9]{8}" required value="{{ old('dni', $client->dni) }}"></label>
        <div class="catalog-fields">
            <label>Nombres<input name="nombres" required maxlength="100" value="{{ old('nombres', $client->nombres) }}"></label>
            <label>Apellidos<input name="apellidos" required maxlength="120" value="{{ old('apellidos', $client->apellidos) }}"></label>
            <label>Teléfono <small>(opcional)</small><input type="tel" name="telefono" maxlength="30" value="{{ old('telefono', $client->telefono) }}"></label>
            <label>Correo <small>(opcional)</small><input type="email" name="email" maxlength="255" value="{{ old('email', $client->email) }}"></label>
        </div>
        <label>Dirección <small>(opcional)</small><input name="direccion" maxlength="255" value="{{ old('direccion', $client->direccion) }}"></label>
        <div class="catalog-actions"><button class="catalog-button catalog-primary" type="submit">Guardar cliente</button><a class="catalog-button" href="{{ route('commercial.clients.index') }}">Cancelar</a></div>
    </form>
</section>
</x-app-layout>
