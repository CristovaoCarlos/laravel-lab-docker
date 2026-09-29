<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);   // não vazar a configuração para outros testes

        parent::tearDown();
    }

    public function test_relationships_between_user_post_and_tags(): void
    {
        $editor = User::factory()->editor()->create();
        $post = Post::factory()->for($editor, 'author')->create();
        $post->tags()->attach(Tag::factory()->count(2)->create());

        $loaded = Post::with(['author', 'tags'])->findOrFail($post->id);

        $this->assertTrue($loaded->author->is($editor));
        $this->assertCount(2, $loaded->tags);
        $this->assertCount(1, $editor->posts);
    }

    public function test_published_scope_filters_drafts_and_future_posts(): void
    {
        Post::factory()->published()->create();
        Post::factory()->create();                                                    // rascunho
        Post::factory()->create(['published_at' => now()->addDay()]);                 // agendado

        $this->assertCount(1, Post::published()->get());
    }

    public function test_title_mutator_trims_and_accessor_capitalizes(): void
    {
        $post = Post::factory()->create(['title' => '  olá mundo  ']);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'olá mundo']);   // mutator
        $this->assertSame('Olá mundo', $post->fresh()->title);                             // accessor
    }

    public function test_soft_delete_keeps_the_row(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSoftDeleted($post);
        $this->assertCount(0, Post::all());
        $this->assertCount(1, Post::withTrashed()->get());
    }

    public function test_with_count_adds_comments_count(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $this->assertSame(3, Post::withCount('comments')->find($post->id)->comments_count);
    }

    public function test_eager_loading_avoids_n_plus_one(): void
    {
        Post::factory()->count(3)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        Post::with('author')->get()->each(fn (Post $post) => $post->author->name);

        $this->assertCount(2, DB::getQueryLog());   // 1 de posts + 1 de autores, não 1 + N
    }

    public function test_lazy_loading_violation_is_thrown_when_prevention_is_enabled(): void
    {
        Post::factory()->count(2)->create();
        Model::preventLazyLoading(true);

        $this->expectException(LazyLoadingViolationException::class);

        Post::all()->each(fn (Post $post) => $post->author);   // N+1 proposital
    }
}
