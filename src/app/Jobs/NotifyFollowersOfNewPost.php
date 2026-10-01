<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job (tarefa) executado em segundo plano pelo worker da fila.
 * Aqui só grava em log; num sistema real enviaria e-mails/notificações.
 */
class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tentativas antes de ir para a tabela failed_jobs. */
    public int $tries = 3;

    /** Espera (segundos) entre as tentativas. */
    public array $backoff = [10, 60, 300];

    public function __construct(public Post $post)
    {
    }

    public function handle(): void
    {
        Log::info('Notificando seguidores sobre novo post', [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha ao notificar seguidores', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
