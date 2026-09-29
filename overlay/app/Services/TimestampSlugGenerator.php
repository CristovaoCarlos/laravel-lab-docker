<?php

namespace App\Services;

use App\Contracts\SlugGenerator;
use Illuminate\Support\Str;

/** Segunda implementação: acrescenta data/hora ao slug (ex.: ola-mundo-20260929153000). */
class TimestampSlugGenerator implements SlugGenerator
{
    public function generate(string $text): string
    {
        return Str::slug($text).'-'.date('YmdHis');
    }
}
