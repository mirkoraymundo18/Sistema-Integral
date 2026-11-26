<?php
namespace Tests\Feature;
use App\Actions\CheckSystemIntegrity;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DeliveryChecksTest extends TestCase {
    use RefreshDatabase;
    public function test_integrity_check_is_read_only_and_reports_a_stock_difference(): void {
        User::factory()->create(['role'=>'ADMINISTRADOR']);
        $this->artisan('sistema:cargar-ejemplos')->assertSuccessful();
        $report=app(CheckSystemIntegrity::class)->check();
        $this->assertSame(0,$report['errors']);$this->assertSame(0,$report['warnings']);
        $product=Product::firstOrFail();$product->stock_actual=999;$product->save();
        $report=app(CheckSystemIntegrity::class)->check();
        $this->assertGreaterThan(0,$report['warnings']);
        $this->assertEquals(999,$product->refresh()->stock_actual);
    }
    public function test_account_restoration_affects_only_selected_account_and_requires_admin(): void {
        $admin=User::factory()->create(['role'=>'ADMINISTRADOR']);
        $first=User::factory()->create(['role'=>'OPERADOR']);$second=User::factory()->create(['role'=>'OPERADOR']);
        $first->delete();$second->delete();
        $this->actingAs($admin)->post(route('users.restore',$first))->assertRedirect(route('users.index'));
        $this->assertNotSoftDeleted($first);$this->assertSoftDeleted($second);
        $this->assertSame('OPERADOR',$first->refresh()->role);
        $this->actingAs($first)->post(route('users.restore',$second))->assertForbidden();
        $this->withoutVite()->get(route('help.index'))->assertOk();
        $this->get(route('system.check'))->assertForbidden();
    }
}
