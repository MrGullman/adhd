<?php

namespace Tests\Unit\Food;

use App\Food\IngredientLine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IngredientLineTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<array{name: string, quantity: float|null, unit: string|null, note: string|null}>}>
     */
    public static function lines(): array
    {
        return [
            'grams' => ['500 g köttfärs', [['name' => 'Köttfärs', 'quantity' => 500.0, 'unit' => 'g', 'note' => null]]],
            'half as a sign' => ['1 ½ dl grädde', [['name' => 'Grädde', 'quantity' => 1.5, 'unit' => 'dl', 'note' => null]]],
            'fraction' => ['1/2 tsk salt', [['name' => 'Salt', 'quantity' => 0.5, 'unit' => 'tsk', 'note' => null]]],
            'whole and fraction' => ['1 1/2 msk smör', [['name' => 'Smör', 'quantity' => 1.5, 'unit' => 'msk', 'note' => null]]],
            'pieces with a note' => ['2 gula lökar, finhackade', [['name' => 'Gula lökar', 'quantity' => 2.0, 'unit' => 'st', 'note' => 'finhackade']]],
            'range takes the first' => ['2-3 vitlöksklyftor', [['name' => 'Vitlöksklyftor', 'quantity' => 2.0, 'unit' => 'st', 'note' => null]]],
            'about' => ['ca 4 dl vatten', [['name' => 'Vatten', 'quantity' => 4.0, 'unit' => 'dl', 'note' => null]]],
            'centilitres as millilitres' => ['3 cl konjak', [['name' => 'Konjak', 'quantity' => 30.0, 'unit' => 'ml', 'note' => null]]],
            'unit words' => ['2 matskedar olja', [['name' => 'Olja', 'quantity' => 2.0, 'unit' => 'msk', 'note' => null]]],
            'two without amount' => ['salt och peppar', [
                ['name' => 'Salt', 'quantity' => null, 'unit' => null, 'note' => null],
                ['name' => 'Peppar', 'quantity' => null, 'unit' => null, 'note' => null],
            ]],
            'html' => ['<b>2 st</b> ägg', [['name' => 'Ägg', 'quantity' => 2.0, 'unit' => 'st', 'note' => null]]],
        ];
    }

    #[DataProvider('lines')]
    public function test_a_line_from_a_recipe_site_is_read(string $line, array $expected)
    {
        $this->assertEquals($expected, IngredientLine::parse($line));
    }
}
