<?php

namespace Tests\Unit;

use App\Services\AsciiSlugGenerator;
use App\Services\TimestampSlugGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Teste Unit: PHP puro, sem subir o framework (rápido).
 */
class SlugGeneratorTest extends TestCase
{
    public function test_ascii_generator_removes_accents_and_punctuation(): void
    {
        $this->assertSame('ola-mundo', (new AsciiSlugGenerator())->generate('Olá, Mundo!'));
    }

    public function test_timestamp_generator_appends_date_and_time(): void
    {
        $slug = (new TimestampSlugGenerator())->generate('Olá, Mundo!');

        $this->assertMatchesRegularExpression('/^ola-mundo-\d{14}$/', $slug);
    }
}
