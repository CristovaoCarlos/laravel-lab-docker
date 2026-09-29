<?php

return [

    // Implementação usada para o contrato App\Contracts\SlugGenerator: "ascii" ou "timestamp".
    'slug_driver' => env('LAB_SLUG_DRIVER', 'ascii'),

    // Palavras por minuto usadas pelo ReadingTimeEstimator (singleton).
    'words_per_minute' => env('LAB_WORDS_PER_MINUTE', 200),

    // Lança exceção ao cair em N+1 (lazy loading). Nunca ativo em produção.
    'prevent_lazy_loading' => env('LAB_PREVENT_LAZY_LOADING', true),

];
