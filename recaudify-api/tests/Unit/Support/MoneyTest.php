<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    // ── roundUpToThousand ──────────────────────────────────────────

    public static function roundUpProvider(): array
    {
        return [
            "exact thousand"       => [12000, 12000],
            "above thousand"       => [12001, 13000],
            "below thousand"       => [1, 1000],
            "just below next"      => [12999, 13000],
            "zero"                 => [0, 0],
            "float above"          => [12000.5, 13000],
            "float exact"          => [12001.0, 13000],
            "negative exact"       => [-12000, -12000],
            "negative below"       => [-12001, -12000],
            "negative above"       => [-11999, -11000],
            "negative float"       => [-12000.5, -12000],
            "small positive"       => [500, 1000],
            "small negative"       => [-500, 0],
        ];
    }

    #[DataProvider("roundUpProvider")]
    public function test_round_up_to_thousand(int|float $input, int $expected): void
    {
        $this->assertSame($expected, Money::roundUpToThousand($input));
    }

    // ── roundToThousand ────────────────────────────────────────────

    public static function roundNearestProvider(): array
    {
        return [
            "below half"           => [12400, 12000],
            "at half rounds up"    => [12500, 13000],
            "above half"           => [12600, 13000],
            "exact boundary up"    => [11500, 12000],
            "negative below half"  => [-12400, -12000],
            "negative at half"     => [-12500, -13000],
            "negative above half"  => [-12600, -13000],
            "zero"                 => [0, 0],
            "float below half"     => [12499.0, 12000],
            "float at half"        => [12500.0, 13000],
            "small positive"       => [400, 0],
            "small negative"       => [-400, 0],
        ];
    }

    #[DataProvider("roundNearestProvider")]
    public function test_round_to_nearest_thousand(int|float $input, int $expected): void
    {
        $this->assertSame($expected, Money::roundToThousand($input));
    }

    // ── fromInput ──────────────────────────────────────────────────

    public static function fromInputValidProvider(): array
    {
        return [
            "int"                  => [50000, 50000],
            "float rounds"         => [49999.6, 50000],
            "float exact"          => [50000.0, 50000],
            "dot formatted"        => ["1.250.000", 1250000],
            "comma formatted"      => ["1,250,000", 1250000],
            "dollar sign"          => ["$ 1.250.000", 1250000],
            "plain string"         => ["12500", 12500],
            "zero int"             => [0, 0],
            "zero string"          => ["0", 0],
            "negative int"         => [-50000, -50000],
            "negative string"      => ["-50000", -50000],
            "negative formatted"   => ["-1.250.000", -1250000],
            "negative comma"       => ["-1,250,000", -1250000],
            "string with spaces"   => [" 42000 ", 42000],
            "dollar and comma"     => ["$ 1,250,000", 1250000],
        ];
    }

    #[DataProvider("fromInputValidProvider")]
    public function test_from_input_accepts_valid(int|float|string $input, int $expected): void
    {
        $this->assertSame($expected, Money::fromInput($input));
    }

    public static function fromInputInvalidProvider(): array
    {
        return [
            "garbage"              => ["abc"],
            "empty"                => ["   "],
            "mixed alphanumeric"   => ["12abc"],
            "mixed letters digits" => ["abc12"],
            "special chars"        => ["@#$"],
        ];
    }

    #[DataProvider("fromInputInvalidProvider")]
    public function test_from_input_rejects_invalid(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromInput($input);
    }

    public function test_from_input_rejects_nan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromInput(NAN);
    }

    public function test_from_input_rejects_inf(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromInput(INF);
    }

    public function test_from_input_rejects_negative_inf(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromInput(-INF);
    }

    // ── split ──────────────────────────────────────────────────────

    public static function splitValidProvider(): array
    {
        return [
            "exact division"       => [100000, 4, [25000, 25000, 25000, 25000]],
            "remainder to first"   => [100000, 3, [33334, 33333, 33333]],
            "small remainder"      => [10, 3, [4, 3, 3]],
            "single part"          => [75000, 1, [75000]],
            "zero amount"          => [0, 3, [0, 0, 0]],
            "amount less than"     => [5, 10, [1, 1, 1, 1, 1, 0, 0, 0, 0, 0]],
            "two parts"            => [9999, 2, [5000, 4999]],
            "all ones"             => [3, 3, [1, 1, 1]],
        ];
    }

    #[DataProvider("splitValidProvider")]
    public function test_split(int $amount, int $parts, array $expected): void
    {
        $result = Money::split($amount, $parts);

        $this->assertSame($expected, $result);
        $this->assertSame($amount, array_sum($result), "Suma de partes debe igualar el monto original");
    }

    public function test_split_rejects_zero_parts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::split(1000, 0);
    }

    public function test_split_rejects_negative_parts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::split(1000, -1);
    }
}
