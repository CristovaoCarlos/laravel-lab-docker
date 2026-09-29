<?php

namespace App\Http\Resources;

use App\Services\ReadingTimeEstimator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => Str::limit($this->body, 120),
            'reading_time_min' => app(ReadingTimeEstimator::class)->minutes($this->body),
            'published' => $this->published_at !== null,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Só aparece na rota de detalhe (posts.show).
            'body' => $this->when($request->routeIs('posts.show'), fn () => $this->body),

            // Só aparecem se o relacionamento/contagem foi carregado (evita N+1 escondido).
            'author' => new UserResource($this->whenLoaded('author')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
