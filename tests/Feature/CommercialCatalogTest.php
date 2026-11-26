<?php

namespace Tests\Feature;

use App\Models\LandLot;
use App\Models\RealEstateProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_project_and_lots_with_unique_identification_within_each_project(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $this->actingAs($admin)->post(route('commercial.projects.store'), [
            'nombre' => 'Proyecto de prueba',
            'ubicacion' => 'Ubicación de prueba',
        ])->assertSessionHasNoErrors();

        $project = RealEstateProject::sole();
        $data = [
            'manzana' => 'a', 'numero' => '01', 'area_m2' => '120.50',
            'precio_total' => '50000.25', 'moneda' => 'PEN',
        ];
        $this->post(route('commercial.lots.store', $project), $data)->assertSessionHasNoErrors();
        $lot = LandLot::sole();
        $this->assertSame('A', $lot->manzana);
        $this->assertSame('120.50', $lot->area_m2);
        $this->assertSame('DISPONIBLE', $lot->estado);
        $this->post(route('commercial.lots.store', $project), $data)->assertSessionHasErrors('numero');

        $other = RealEstateProject::create(['nombre' => 'Otro proyecto', 'ubicacion' => 'Otra ubicación']);
        $this->post(route('commercial.lots.store', $other), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('land_lots', 2);
    }

    public function test_state_change_needs_reason_and_stale_updates_cannot_overwrite_a_lot(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $project = RealEstateProject::create(['nombre' => 'Proyecto', 'ubicacion' => 'Ubicación']);
        $this->actingAs($admin)->post(route('commercial.lots.store', $project), [
            'manzana' => 'A', 'numero' => '01', 'area_m2' => 120, 'precio_total' => 50000, 'moneda' => 'PEN',
        ]);
        $lot = LandLot::sole();
        $data = [
            'manzana' => 'A', 'numero' => '01', 'area_m2' => 120, 'precio_total' => 50000, 'moneda' => 'PEN',
            'version' => 1, 'estado' => 'RESERVADO',
        ];
        $this->patch(route('commercial.lots.update', $lot), $data)->assertSessionHasErrors('motivo_estado');
        $this->patch(route('commercial.lots.update', $lot), [...$data, 'motivo_estado' => 'Confirmación de administración'])
            ->assertSessionHasNoErrors();
        $this->patch(route('commercial.lots.update', $lot), [
            ...$data, 'estado' => 'VENDIDO', 'motivo_estado' => 'Cambio posterior',
        ])->assertSessionHasErrors('lote');
        $this->assertSame('RESERVADO', $lot->refresh()->estado);
        $this->assertSame(2, (int) $lot->version);
    }

    public function test_operator_consults_projects_but_cannot_create_or_edit_lots(): void
    {
        $operator = User::factory()->create();
        $project = RealEstateProject::create(['nombre' => 'Proyecto', 'ubicacion' => 'Ubicación']);
        $this->withoutVite()->actingAs($operator)->get(route('commercial.projects.index'))->assertOk();
        $this->get(route('commercial.projects.show', $project))->assertOk();
        $this->post(route('commercial.projects.store'), [])->assertForbidden();
        $this->get(route('commercial.lots.create', $project))->assertForbidden();
        $this->post(route('commercial.lots.store', $project), [])->assertForbidden();
    }
}
