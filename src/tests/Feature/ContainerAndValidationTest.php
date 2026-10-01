<?php

namespace Tests\Feature;

use App\Contracts\SlugGenerator;
use App\Models\Post;
use App\Models\User;
use App\Services\AsciiSlugGenerator;
use App\Services\ReadingTimeEstimator;
use App\Services\TimestampSlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Service Provider / service container e regra de validação customizada (via HTTP).
 */
class ContainerAndValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_container_resolves_the_interface_to_the_configured_implementation(): void
    {
        $this->assertInstanceOf(AsciiSlugGenerator::class, app(SlugGenerator::class));

        config(['lab.slug_driver' => 'timestamp']);

        $this->assertInstanceOf(TimestampSlugGenerator::class, app(SlugGenerator::class));
    }

    public function test_singleton_returns_the_same_instance(): void
    {
        $this->assertSame(app(ReadingTimeEstimator::class), app(ReadingTimeEstimator::class));
        $this->assertNotSame(app(SlugGenerator::class), app(SlugGenerator::class));   // bind: novo objeto
    }

    public function test_a_fake_implementation_can_replace_the_binding_in_tests(): void
    {
        $this->mock(SlugGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->andReturn('slug-fixo');
        });

        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', ['title' => 'Qualquer', 'body' => str_repeat('texto ', 10)])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'slug-fixo');

        $this->assertSame(1, Post::where('slug', 'slug-fixo')->count());
    }

    public function test_cpf_endpoint_accepts_a_valid_cpf(): void
    {
        $this->postJson('/api/utils/cpf', ['cpf' => '529.982.247-25'])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_cpf_endpoint_rejects_an_invalid_cpf(): void
    {
        $this->postJson('/api/utils/cpf', ['cpf' => '111.111.111-11'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cpf');
    }
}
