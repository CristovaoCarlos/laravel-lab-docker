<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfRuleTest extends TestCase
{
    private function fails(string $value): bool
    {
        $failed = false;

        (new Cpf())->validate('cpf', $value, function () use (&$failed) {
            $failed = true;
        });

        return $failed;
    }

    #[DataProvider('validCpfs')]
    public function test_valid_cpfs_pass(string $cpf): void
    {
        $this->assertFalse($this->fails($cpf));
    }

    #[DataProvider('invalidCpfs')]
    public function test_invalid_cpfs_fail(string $cpf): void
    {
        $this->assertTrue($this->fails($cpf));
    }

    public static function validCpfs(): array
    {
        return [
            'com máscara' => ['529.982.247-25'],
            'sem máscara' => ['52998224725'],
        ];
    }

    public static function invalidCpfs(): array
    {
        return [
            'dígitos repetidos' => ['111.111.111-11'],
            'dígito verificador errado' => ['529.982.247-24'],
            'tamanho errado' => ['123'],
            'vazio' => [''],
        ];
    }
}
