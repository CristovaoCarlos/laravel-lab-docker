<?php

namespace App\Services;

use App\Contracts\SlugGenerator;
use Illuminate\Support\Str;

class AsciiSlugGenerator implements SlugGenerator
{
    public function generate(string $text): string
    {
        return Str::slug($text);
    }
}
