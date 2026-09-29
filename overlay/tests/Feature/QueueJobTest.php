<?php

namespace Tests\Feature;

use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Filas: Bus::fake() impede a execução real e permite verificar o que foi despachado.
 */
class QueueJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_dispatches_the_job_once(): void
    {
        Bus::fake();

        $editor = User::factory()->editor()->create();
        $post = Post::factory()->for($editor, 'author')->create();
        Sanctum::actingAs($editor);

        $this->postJson("/api/posts/{$post->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.published', true);

        // Publicar de novo não despacha outro job.
        $this->postJson("/api/posts/{$post->id}/publish")->assertOk();

        Bus::assertDispatchedTimes(NotifyFollowersOfNewPost::class, 1);
        Bus::assertDispatched(NotifyFollowersOfNewPost::class, fn ($job) => $job->post->is($post));
    }

    public function test_reader_cannot_publish(): void
    {
        Bus::fake();

        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/posts/{$post->id}/publish")->assertForbidden();

        Bus::assertNothingDispatched();
    }

    public function test_job_handle_writes_to_the_log(): void
    {
        Log::spy();

        $post = Post::factory()->create();

        (new NotifyFollowersOfNewPost($post))->handle();

        Log::shouldHaveReceived('info')->once();
    }

    public function test_job_declares_retry_policy(): void
    {
        $job = new NotifyFollowersOfNewPost(Post::factory()->create());

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60, 300], $job->backoff);
    }
}
