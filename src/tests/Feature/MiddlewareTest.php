<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_me_endpoint(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_inactive_user_is_blocked_by_active_middleware(): void
    {
        Sanctum::actingAs(User::factory()->inactive()->create());

        $this->getJson('/api/me')->assertForbidden();
    }

    public function test_active_user_gets_own_data(): void
    {
        $user = Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_api_group_middleware_adds_response_time_header(): void
    {
        $this->getJson('/api/posts')->assertHeader('X-Response-Time');
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'aluno@lab.test']);   // senha: "password"

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
