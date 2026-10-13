<?php

namespace Tests\Unit\Food;

use App\Food\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuantityTest extends TestCase
{
    /**
     * @return array<string, array{float, string, array{0: float, 1: string|null}}>
     */
    public static function amounts(): array
    {
        return [
            'gram' => [450, 'weight', [450.0, 'g']],
            'kilo' => [1100, 'weight', [1.1, 'kg']],
            'liter' => [1500, 'volume', [1.5, 'l']],
            'deciliter' => [330, 'volume', [3.3, 'dl']],
            'matsked' => [30, 'volume', [2.0, 'msk']],
            'tesked' => [10, 'volume', [2.0, 'tsk']],
            'kryddmått' => [2, 'volume', [2.0, 'krm']],
            'styck' => [3, 'st', [3.0, 'st']],
            'halv påse köps hel' => [1.5, 'påse', [2.0, 'påse']],
            'styck avrundas uppåt' => [10.2, 'st', [11.0, 'st']],
        ];
    }

    #[DataProvider('amounts')]
    public function test_amounts_are_shown_in_a_readable_unit(float $base, string $group, array $expected)
    {
        $this->assertSame($expected, Quantity::readable($base, $group));
    }

    public function test_weight_and_volume_are_added_up_in_their_base_unit()
    {
        $this->assertSame('weight', Quantity::group('kg'));
        $this->assertSame('volume', Quantity::group('msk'));
        $this->assertSame('förp', Quantity::group('förp'));
        $this->assertSame(500.0, Quantity::toBase(0.5, 'kg'));
        $this->assertSame(45.0, Quantity::toBase(3, 'msk'));
        $this->assertSame(300.0, Quantity::toBase(3, 'dl'));
    }
}
