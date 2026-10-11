<?php

namespace Tests\Unit\Support;

use App\Support\ChildPins;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChildPinsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function tooSimple(): array
    {
        return [
            'samma siffra' => ['0000'],
            'samma siffra, sex siffror' => ['999999'],
            'stigande följd' => ['1234'],
            'stigande från noll' => ['012345'],
            'fallande följd' => ['4321'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function acceptable(): array
    {
        return [
            'fyra blandade' => ['4826'],
            'nästan följd' => ['1235'],
            'upprepat par' => ['1212'],
            'sex blandade' => ['730194'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badFormat(): array
    {
        return [
            'för kort' => ['482'],
            'för lång' => ['4826193'],
            'bokstäver' => ['48a6'],
            'mellanslag' => ['48 26'],
        ];
    }

    #[DataProvider('tooSimple')]
    public function test_too_simple_codes_are_rejected(string $pin)
    {
        $this->assertTrue(ChildPins::isTooSimple($pin));
    }

    #[DataProvider('acceptable')]
    public function test_varied_codes_are_accepted(string $pin)
    {
        $this->assertTrue(ChildPins::hasValidFormat($pin));
        $this->assertFalse(ChildPins::isTooSimple($pin));
    }

    #[DataProvider('badFormat')]
    public function test_codes_must_be_four_to_six_digits(string $pin)
    {
        $this->assertFalse(ChildPins::hasValidFormat($pin));
    }

    public function test_generated_codes_follow_the_rules()
    {
        for ($i = 0; $i < 200; $i++) {
            $pin = ChildPins::generate();

            $this->assertTrue(ChildPins::hasValidFormat($pin));
            $this->assertFalse(ChildPins::isTooSimple($pin));
        }
    }
}
