<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Usuários de estudo (senha de todos: "password"):
     *   admin@lab.test, editor@lab.test, leitor@lab.test
     */
    public function run(): void
    {
        User::factory()->admin()->create(['name' => 'Admin Lab', 'email' => 'admin@lab.test']);
        $editor = User::factory()->editor()->create(['name' => 'Editor Lab', 'email' => 'editor@lab.test']);
        User::factory()->create(['name' => 'Leitor Lab', 'email' => 'leitor@lab.test']);

        $readers = User::factory()->count(4)->create();
        $tags = Tag::factory()->count(8)->create();

        // 15 posts publicados, cada um com 1 a 3 tags e 0 a 4 comentários.
        Post::factory()->count(15)->published()->for($editor, 'author')->create()
            ->each(function (Post $post) use ($tags, $readers) {
                $post->tags()->attach($tags->random(rand(1, 3))->pluck('id')->all());

                Comment::factory()->count(rand(0, 4))->create([
                    'post_id' => $post->id,
                    'user_id' => $readers->random()->id,
                ]);
            });

        // 4 rascunhos (não aparecem na API pública).
        Post::factory()->count(4)->for($editor, 'author')->create();
    }
}
