<?php

namespace Tests\Unit;

use App\Services\ReadingTimeEstimator;
use PHPUnit\Framework\TestCase;

class ReadingTimeEstimatorTest extends TestCase
{
    public function test_short_text_takes_at_least_one_minute(): void
    {
        $this->assertSame(1, (new ReadingTimeEstimator(200))->minutes(''));
        $this->assertSame(1, (new ReadingTimeEstimator(200))->minutes('poucas palavras'));
    }

    public function test_minutes_are_rounded_up(): void
    {
        $text = trim(str_repeat('palavra ', 401));

        $this->assertSame(3, (new ReadingTimeEstimator(200))->minutes($text));   // 401 palavras / 200
    }

    public function test_html_tags_are_ignored(): void
    {
        $this->assertSame(1, (new ReadingTimeEstimator(2))->minutes('<p><strong>um</strong> dois</p>'));
    }
}
