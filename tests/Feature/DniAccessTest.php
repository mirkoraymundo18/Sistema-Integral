<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DniAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_dni_with_leading_zero_and_email(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['dni' => '00123456'])->save();

        $this->post('/login', ['email' => '00123456', 'password' => 'password'])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_dni_requires_password_and_disabled_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['dni' => '00123456'])->save();

        $this->post('/login', ['email' => '00123456', 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->delete();
        $this->post('/login', ['email' => '00123456', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_assigns_unique_dni_and_retains_it_when_omitted_by_existing_callers(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRADOR']);
        $user = User::factory()->create();
        $data = ['name' => $user->name, 'email' => $user->email, 'role' => 'OPERADOR'];

        $this->actingAs($admin)->patch(route('users.update', $user), [...$data, 'dni' => '00123456'])
            ->assertSessionHasNoErrors();
        $this->patch(route('users.update', $user), $data)->assertSessionHasNoErrors();
        $this->assertSame('00123456', $user->refresh()->dni);

        $user->delete();
        $other = User::factory()->create();
        $this->patch(route('users.update', $other), [
            'name' => $other->name, 'email' => $other->email, 'role' => 'OPERADOR', 'dni' => '00123456',
        ])->assertSessionHasErrors('dni');
    }
}
