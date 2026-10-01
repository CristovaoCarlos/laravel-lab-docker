<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Jobs\NotifyFollowersOfNewPost;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /** Lista pública: só posts publicados, paginados (15 por página). */
    public function index()
    {
        // with() = eager loading: 3 queries no total, sem N+1.
        $posts = Post::published()
            ->with(['author', 'tags'])
            ->withCount('comments')
            ->latest('published_at')
            ->paginate(15);

        return PostResource::collection($posts);
    }

    /** Detalhe público: rascunhos respondem 404. */
    public function show(Post $post)
    {
        abort_unless($post->published_at?->isPast(), 404);

        return new PostResource($post->load(['author', 'tags'])->loadCount('comments'));
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $post = $request->user()->posts()->create($data);
        $post->tags()->sync($tags);

        return (new PostResource($post->load(['author', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $post->update($data);

        if ($tags !== null) {
            $post->tags()->sync($tags);
        }

        return new PostResource($post->load(['author', 'tags']));
    }

    public function destroy(Post $post): Response
    {
        Gate::authorize('delete', $post);

        $post->delete();   // soft delete: apenas preenche deleted_at

        return response()->noContent();
    }

    /** Publica o post e dispara o job na fila (só na primeira publicação). */
    public function publish(Post $post)
    {
        Gate::authorize('update', $post);

        if ($post->published_at === null) {
            $post->update(['published_at' => now()]);

            NotifyFollowersOfNewPost::dispatch($post)->afterCommit();
        }

        return new PostResource($post->load(['author', 'tags']));
    }
}
