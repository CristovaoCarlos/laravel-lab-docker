<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            // Campo condicional: e-mail só aparece para administradores.
            'email' => $this->when($request->user('sanctum')?->isAdmin(), fn () => $this->email),
        ];
    }
}
