<?php

namespace Tests\Feature;

use App\Actions\ExpireLotReservations;
use App\Models\Client;
use App\Models\LandLot;
use App\Models\LotReservation;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LotReservationTest extends TestCase
{
    use RefreshDatabase;

    private function commercialData(): array
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $project = RealEstateProject::create(['nombre' => 'Proyecto de prueba', 'ubicacion' => 'Lima']);
        $lot = $project->lots()->forceCreate(['updated_by'=>$admin->id, 
            'manzana' => 'A', 'numero' => '01', 'area_m2' => 120,
            'precio_total' => 50000, 'moneda' => 'PEN', 'estado' => 'DISPONIBLE',
        ]);
        $client = new Client(['dni' => '01234567', 'nombres' => 'Cliente', 'apellidos' => 'De prueba']);
        $client->created_by = $admin->id;
        $client->save();
        $this->withoutVite()->actingAs($admin);

        return [$lot, $client, [
            'token' => (string) Str::uuid(), 'land_lot_id' => $lot->id, 'client_id' => $client->id,
            'fecha_vencimiento' => now('America/Lima')->addDay()->toDateString(), 'precio_acordado' => '49500.25',
        ]];
    }

    public function test_dni_preserves_leading_zero_and_rejects_duplicates(): void
    {
        [, $client] = $this->commercialData();
        $this->assertSame('01234567', $client->dni);
        $this->post(route('commercial.clients.store'), [
            'dni' => $client->dni, 'nombres' => 'Otro', 'apellidos' => 'Cliente',
        ])->assertSessionHasErrors('dni');
        $this->post(route('commercial.clients.store'), [
            'dni' => '123', 'nombres' => 'Otro', 'apellidos' => 'Cliente',
        ])->assertSessionHasErrors('dni');
        $this->assertDatabaseCount('clients', 1);
        $this->get(route('commercial.clients.index'))->assertOk();
        $this->get(route('commercial.clients.show', $client))->assertOk();
        $this->get(route('commercial.reservations.create'))->assertOk();
    }

    public function test_reserving_is_idempotent_blocks_other_reservations_and_cancellation_releases_lot(): void
    {
        [$lot, $client, $data] = $this->commercialData();
        $this->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $reservation = LotReservation::sole();
        $this->assertSame('RESERVADO', $lot->refresh()->estado);
        $this->assertSame('49500.25', $reservation->precio_acordado);
        $this->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_reservations', 1);
        $this->post(route('commercial.reservations.store'), [...$data, 'token' => (string) Str::uuid()])
            ->assertSessionHasErrors('lote');
        $this->patch(route('commercial.lots.update', $lot), ['version' => $lot->version])
            ->assertSessionHasErrors('reserva');
        $client->update(['nombres' => 'Nombre actualizado']);
        $this->get(route('commercial.reservations.show', $reservation))->assertOk()->assertSee('Cliente De prueba');
        $this->post(route('commercial.reservations.cancel', $reservation), ['motivo_cancelacion' => 'El cliente desistió'])
            ->assertSessionHasNoErrors();
        $this->assertSame('DISPONIBLE', $lot->refresh()->estado);
        $this->assertSame('CANCELADA', $reservation->refresh()->estado);
        $this->assertNull($reservation->active_lot_id);
        $this->post(route('commercial.reservations.store'), [...$data, 'token' => (string) Str::uuid()])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lot_reservations', 2);
    }

    public function test_expiration_uses_the_end_of_selected_day_in_peru_and_preserves_history(): void
    {
        [$lot, , $data] = $this->commercialData();
        $this->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $reservation = LotReservation::sole();
        $this->assertSame('00:00:00', $reservation->vence_en->copy()->timezone('America/Lima')->format('H:i:s'));
        $this->assertSame($data['fecha_vencimiento'], $reservation->vence_en->copy()->timezone('America/Lima')->subSecond()->toDateString());
        $this->travelTo($reservation->vence_en->copy()->subSecond());
        app(ExpireLotReservations::class)->run();
        $this->assertSame('ACTIVA', $reservation->refresh()->estado);
        $this->travelTo($reservation->vence_en->copy());
        app(ExpireLotReservations::class)->run();
        $this->assertSame('VENCIDA', $reservation->refresh()->estado);
        $this->assertNull($reservation->active_lot_id);
        $this->assertSame('DISPONIBLE', $lot->refresh()->estado);
        $this->assertDatabaseCount('lot_reservations', 1);
        $this->travelBack();
    }

    public function test_operator_can_manage_clients_and_reservations_but_cannot_manage_lots_or_accounts(): void
    {
        [$lot, $client, $data] = $this->commercialData();
        $operator = User::factory()->create(['role' => 'OPERADOR']);
        $this->actingAs($operator);
        $this->get(route('commercial.clients.index'))->assertOk()->assertSee('Clientes')->assertSee('Reservas');
        $this->get(route('commercial.clients.show', $client))->assertOk();
        $this->post(route('commercial.clients.store'), [
            'dni' => '73591842', 'nombres' => 'Nuevo cliente', 'apellidos' => 'De prueba',
        ])->assertSessionHasNoErrors();
        $newClient = Client::where('dni', '73591842')->firstOrFail();
        $this->assertSame($operator->id, $newClient->created_by);
        $this->patch(route('commercial.clients.update', $newClient), [
            'dni' => $newClient->dni, 'nombres' => $newClient->nombres, 'apellidos' => 'Actualizado',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Actualizado', $newClient->refresh()->apellidos);
        $this->get(route('commercial.reservations.index'))->assertOk();
        $this->get(route('commercial.lots.show', $lot))->assertOk()->assertSee('Reservar este lote')->assertDontSee('Editar ficha');
        $this->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $reservation = LotReservation::sole();
        $this->assertSame($operator->id, $reservation->created_by);
        $this->get(route('commercial.reservations.show', $reservation))->assertOk()->assertSee('Cancelar esta reserva');
        $this->get(route('commercial.lots.edit', $lot))->assertForbidden();
        $this->patch(route('commercial.lots.update', $lot), [])->assertForbidden();
        $this->post(route('commercial.projects.store'), [])->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('products.store'), [])->assertForbidden();
    }

    public function test_operator_can_cancel_own_reservation_but_cannot_cancel_another_workers_reservation(): void
    {
        [$lot, , $data] = $this->commercialData();
        $creator = User::factory()->create(['role' => 'OPERADOR']);
        $other = User::factory()->create(['role' => 'OPERADOR']);
        $this->actingAs($creator)->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $reservation = LotReservation::sole();
        $this->actingAs($other)->get(route('commercial.reservations.show', $reservation))
            ->assertOk()->assertDontSee('Cancelar esta reserva');
        $this->post(route('commercial.reservations.cancel', $reservation), ['motivo_cancelacion' => 'Intento de otro operador'])
            ->assertForbidden();
        $this->assertSame('ACTIVA', $reservation->refresh()->estado);
        $this->assertSame('RESERVADO', $lot->refresh()->estado);
        $this->actingAs($creator)->post(route('commercial.reservations.cancel', $reservation), ['motivo_cancelacion' => 'El cliente desistió'])
            ->assertSessionHasNoErrors();
        $this->assertSame('CANCELADA', $reservation->refresh()->estado);
        $this->assertSame('DISPONIBLE', $lot->refresh()->estado);
    }

    public function test_administrator_can_cancel_a_reservation_created_by_an_operator(): void
    {
        [, , $data] = $this->commercialData();
        $admin = User::where('role', 'ADMINISTRADOR')->firstOrFail();
        $operator = User::factory()->create(['role' => 'OPERADOR']);
        $this->actingAs($operator)->post(route('commercial.reservations.store'), $data)->assertSessionHasNoErrors();
        $reservation = LotReservation::sole();
        $this->actingAs($admin)->post(route('commercial.reservations.cancel', $reservation), ['motivo_cancelacion' => 'Confirmación de administración'])
            ->assertSessionHasNoErrors();
        $this->assertSame('CANCELADA', $reservation->refresh()->estado);
        $this->assertSame($admin->id, $reservation->cancelled_by);
    }

    public function test_guest_cannot_access_clients_or_reservations(): void
    {
        $this->get(route('commercial.clients.index'))->assertRedirect(route('login'));
        $this->get(route('commercial.reservations.index'))->assertRedirect(route('login'));
        $this->post(route('commercial.clients.store'), [])->assertRedirect(route('login'));
        $this->post(route('commercial.reservations.store'), [])->assertRedirect(route('login'));
    }
}
