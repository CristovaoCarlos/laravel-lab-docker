<?php

namespace App\Contracts;

/**
 * Interface (contrato) resolvida pelo service container.
 * A implementação concreta é escolhida no LabServiceProvider.
 */
interface SlugGenerator
{
    public function generate(string $text): string;
}
