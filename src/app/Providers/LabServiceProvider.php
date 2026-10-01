<?php

namespace App\Providers;

use App\Contracts\SlugGenerator;
use App\Services\AsciiSlugGenerator;
use App\Services\ReadingTimeEstimator;
use App\Services\TimestampSlugGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class LabServiceProvider extends ServiceProvider
{
    /**
     * register(): só bindings no container. Não use outros serviços aqui,
     * pois eles podem ainda não estar registrados.
     */
    public function register(): void
    {
        // bind: um novo objeto a cada resolução. A implementação vem da config.
        $this->app->bind(SlugGenerator::class, function () {
            return match (config('lab.slug_driver')) {
                'timestamp' => new TimestampSlugGenerator(),
                default => new AsciiSlugGenerator(),
            };
        });

        // singleton: uma única instância compartilhada.
        $this->app->singleton(ReadingTimeEstimator::class, function () {
            return new ReadingTimeEstimator((int) config('lab.words_per_minute'));
        });
    }

    /**
     * boot(): roda depois que todos os providers foram registrados.
     */
    public function boot(): void
    {
        // Lança exceção quando alguém cai no problema N+1 (só fora de produção).
        Model::preventLazyLoading(
            (bool) config('lab.prevent_lazy_loading') && ! $this->app->isProduction()
        );
    }
}
