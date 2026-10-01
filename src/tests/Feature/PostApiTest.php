<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Testes de Feature (fluxo completo: rota, middleware, Form Request, banco, Resource).
 * RefreshDatabase roda as migrations e isola cada teste em uma transação.
 */
class PostApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Meu primeiro post',
            'body' => str_repeat('conteudo ', 10),
        ], $overrides);
    }

    // ---- Criação (Form Request + policy + middlewares) -------------------

    public function test_editor_can_create_post_and_slug_is_generated(): void
    {
        $editor = User::factory()->editor()->create();
        Sanctum::actingAs($editor);

        $this->postJson('/api/posts', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Meu primeiro post')
            ->assertJsonPath('data.slug', 'meu-primeiro-post');

        $this->assertDatabaseHas('posts', ['slug' => 'meu-primeiro-post', 'user_id' => $editor->id]);
    }

    public function test_post_can_be_created_with_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', $this->payload(['tags' => $tags->pluck('id')->all()]))
            ->assertCreated();

        $this->assertDatabaseCount('post_tag', 2);
    }

    public function test_title_is_required(): void
    {
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', ['body' => str_repeat('x', 30)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_body_must_have_at_least_20_characters(): void
    {
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', $this->payload(['body' => 'curto']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_slug_must_be_unique(): void
    {
        Post::factory()->create(['slug' => 'meu-primeiro-post']);
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    public function test_unknown_tag_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->postJson('/api/posts', $this->payload(['tags' => [9999]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tags.0');
    }

    public function test_guest_gets_401(): void
    {
        $this->postJson('/api/posts', $this->payload())->assertUnauthorized();
    }

    public function test_reader_gets_403_from_role_middleware(): void
    {
        Sanctum::actingAs(User::factory()->create());   // role "reader"

        $this->postJson('/api/posts', $this->payload())->assertForbidden();
    }

    public function test_inactive_editor_gets_403_from_active_middleware(): void
    {
        Sanctum::actingAs(User::factory()->editor()->inactive()->create());

        $this->postJson('/api/posts', $this->payload())->assertForbidden();
    }

    // ---- Atualização, exclusão e publicação (policy) ---------------------

    public function test_editor_can_update_own_post_keeping_its_slug(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->for($editor, 'author')->create(['slug' => 'meu-slug']);
        Sanctum::actingAs($editor);

        // O mesmo slug é aceito porque a regra unique ignora o próprio registro.
        $this->putJson("/api/posts/{$post->id}", ['slug' => 'meu-slug', 'title' => 'Novo título'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Novo título');
    }

    public function test_editor_cannot_update_someone_elses_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->editor()->create());

        $this->putJson("/api/posts/{$post->id}", ['title' => 'Invasão'])->assertForbidden();
    }

    public function test_admin_can_soft_delete_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

        $this->assertSoftDeleted($post);
    }

    public function test_editor_cannot_delete_post(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->for($editor, 'author')->create();
        Sanctum::actingAs($editor);

        $this->deleteJson("/api/posts/{$post->id}")->assertForbidden();
    }

    // ---- Leitura pública (API Resources) --------------------------------

    public function test_index_lists_only_published_posts_with_pagination(): void
    {
        Post::factory()->count(2)->published()->create();
        Post::factory()->create();   // rascunho

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'excerpt', 'reading_time_min', 'author' => ['id', 'name'], 'tags', 'comments_count']],
                'links',
                'meta',
            ])
            ->assertJsonMissingPath('data.0.body');   // corpo só no detalhe
    }

    public function test_show_returns_body_for_published_post(): void
    {
        $post = Post::factory()->published()->create(['body' => 'Corpo completo do post publicado.']);

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.body', 'Corpo completo do post publicado.');
    }

    public function test_show_returns_404_for_draft(): void
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")->assertNotFound();
    }

    public function test_author_email_is_hidden_from_guests_and_visible_to_admins(): void
    {
        $post = Post::factory()->published()->create();

        $this->getJson("/api/posts/{$post->id}")->assertJsonMissingPath('data.author.email');

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/posts/{$post->id}")
            ->assertJsonPath('data.author.email', $post->author->email);
    }
}
