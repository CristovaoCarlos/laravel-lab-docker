<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Resources\UserResource;
use App\Rules\Cpf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Todas as rotas deste arquivo têm o prefixo /api.

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Utilitário: valida um CPF com a regra customizada App\Rules\Cpf.
Route::post('/utils/cpf', function (Request $request) {
    $request->validate(['cpf' => ['required', 'string', new Cpf]]);

    return response()->json(['valid' => true]);
});

// Rotas públicas (somente posts publicados).
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

// Rotas autenticadas: token Sanctum + usuário ativo (middlewares "auth:sanctum" e "active").
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', fn (Request $request) => new UserResource($request->user()));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Só admin e editor (middleware com parâmetros).
    Route::middleware('role:admin,editor')->group(function () {
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
    });

    // Só admin.
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('posts.destroy');
});
