<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_return_home_and_commercial_forms_return_to_their_parent(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $project = RealEstateProject::create(['nombre' => 'Proyecto', 'ubicacion' => 'Huancayo']);
        $lot = $project->lots()->forceCreate(['updated_by'=>$admin->id, 
            'manzana' => 'A', 'numero' => '01', 'area_m2' => 120, 'precio_total' => 40000, 'moneda' => 'PEN', 'estado' => 'DISPONIBLE',
        ]);
        $client = new Client(['dni' => '71234567', 'nombres' => 'Cliente', 'apellidos' => 'De prueba']);
        $client->created_by = $admin->id;
        $client->save();
        $this->withoutVite()->actingAs($admin);
        $this->get(route('commercial.clients.index'))->assertOk()->assertSee('Volver al inicio');
        $this->get(route('commercial.projects.show', $project))->assertOk()->assertSee('Volver a proyectos');
        $this->get(route('commercial.lots.show', $lot))->assertOk()->assertSee('Volver a los lotes del proyecto');
        $this->get(route('commercial.lots.edit', $lot))->assertOk()->assertSee('Volver a la ficha del lote');
        $this->get(route('commercial.clients.edit', $client))->assertOk()->assertSee('Volver a la ficha');
        $this->get(route('commercial.reservations.create'))->assertOk()->assertSee('Volver a reservas');
        $this->get(route('products.create'))->assertOk()->assertSee('Volver a productos');
    }
}
