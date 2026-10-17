<?php

namespace Tests\Unit\Food;

use App\Food\CookingSteps;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CookingStepsTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<int>}>
     */
    public static function times(): array
    {
        return [
            'minutes' => ['Koka pastan i 10 minuter.', [600]],
            'short minutes' => ['Låt stå 5 min.', [300]],
            'range takes the first' => ['Koka i ca 10–12 minuter', [600]],
            'range with till' => ['Grädda 20 till 25 min', [1200]],
            'hours with a comma' => ['Låt gryta puttra 1,5 timmar', [5400]],
            'one hour in words' => ['Låt degen jäsa en timme', [3600]],
            'a quarter' => ['Vila köttet en kvart', [900]],
            'seconds' => ['Mixa 30 sekunder', [30]],
            'two times' => ['Stek 3 min per sida, låt sedan vila 10 min', [180, 600]],
            'the same time once' => ['Koka 5 min, rör om och koka 5 min till', [300]],
            'no time in amounts' => ['Tillsätt 2 msk smör och 3 st ägg', []],
            'no time in temperatures' => ['Sätt ugnen på 225 grader', []],
            'not a word that starts like a unit' => ['Ta minst 2 minuter', [120]],
            'too short' => ['Rör 5 sek', []],
        ];
    }

    /**
     * @param  list<int>  $seconds
     */
    #[DataProvider('times')]
    public function test_times_in_a_step_become_timers(string $text, array $seconds): void
    {
        $this->assertSame($seconds, array_column(CookingSteps::timers($text), 'seconds'));
    }

    public function test_instructions_become_numbered_steps_with_headings(): void
    {
        $steps = CookingSteps::from("1. Sätt ugnen på 200 grader.\n\nSåsen:\n2) Koka grädden i 5 minuter.\n- Smaka av.");

        $this->assertSame(['Sätt ugnen på 200 grader.', 'Koka grädden i 5 minuter.', 'Smaka av.'], array_column($steps, 'text'));
        $this->assertSame([1, 2, 3], array_column($steps, 'number'));
        $this->assertSame([null, 'Såsen', 'Såsen'], array_column($steps, 'section'));
        $this->assertSame([['seconds' => 300, 'label' => '5 min']], $steps[1]['timers']);
        $this->assertSame([], CookingSteps::from(null));
    }

    public function test_timer_labels_are_short()
    {
        $this->assertSame('45 sek', CookingSteps::label(45));
        $this->assertSame('10 min', CookingSteps::label(600));
        $this->assertSame('1 h 30 min', CookingSteps::label(5400));
        $this->assertSame('2 h', CookingSteps::label(7200));
    }

    public function test_a_step_knows_which_ingredients_it_uses(): void
    {
        $ingredients = [1 => ['Pasta'], 2 => ['Gul lök'], 3 => ['Vitlök'], 4 => ['Vetemjöl'], 5 => ['Mjöl'], 6 => ['Smör', 'margarin'], 7 => ['Ost'], 8 => ['Ägg']];

        $this->assertSame([1], CookingSteps::ingredientsIn('Koka pastan i 10 minuter.', $ingredients));
        $this->assertSame([2, 3], CookingSteps::ingredientsIn('Hacka löken och vitlöken fint.', $ingredients));
        $this->assertSame([3], CookingSteps::ingredientsIn('Pressa vitlöken.', $ingredients), 'lök is not found in vitlök');
        $this->assertSame([4], CookingSteps::ingredientsIn('Vispa ner vetemjölet.', $ingredients), 'mjöl is not found in vetemjöl');
        $this->assertSame([6], CookingSteps::ingredientsIn('Smält margarinet i en kastrull.', $ingredients));
        $this->assertSame([8, 7], CookingSteps::ingredientsIn('Vispa äggen och riv osten.', $ingredients));
        $this->assertSame([], CookingSteps::ingredientsIn('Sätt ugnen på 225 grader.', $ingredients));
    }
}
