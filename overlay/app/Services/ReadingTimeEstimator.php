<?php

namespace App\Services;

/**
 * Registrado como singleton no LabServiceProvider (uma única instância compartilhada).
 */
class ReadingTimeEstimator
{
    public function __construct(private int $wordsPerMinute = 200)
    {
    }

    /** Minutos de leitura (mínimo 1). */
    public function minutes(string $text): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words) / max(1, $this->wordsPerMinute)));
    }
}
