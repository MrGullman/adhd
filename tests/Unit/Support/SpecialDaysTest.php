<?php

namespace Tests\Unit\Support;

use App\Support\SpecialDays;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpecialDaysTest extends TestCase
{
    /**
     * Easter 2026 is 5 April, 2027 is 28 March.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function adultDays(): array
    {
        return [
            'långfredagen' => ['2026-04-03', ['Röd dag: Långfredagen']],
            'påskdagen' => ['2026-04-05', ['Röd dag: Påskdagen', 'Flaggdag']],
            'annandag påsk 2027' => ['2027-03-29', ['Röd dag: Annandag påsk']],
            'kristi himmelsfärd' => ['2026-05-14', ['Röd dag: Kristi himmelsfärds dag']],
            'valborg' => ['2026-04-30', ['Valborg', 'Flaggdag: Konungens födelsedag']],
            'mors dag' => ['2026-05-31', ['Mors dag']],
            'midsommarafton' => ['2026-06-19', ['Midsommarafton']],
            'midsommardagen' => ['2026-06-20', ['Röd dag: Midsommardagen', 'Flaggdag']],
            'alla helgons dag' => ['2026-10-31', ['Röd dag: Alla helgons dag']],
            'fars dag' => ['2026-11-08', ['Fars dag']],
            'fn-dagen' => ['2026-10-24', ['Flaggdag: FN-dagen']],
            'julafton' => ['2026-12-24', ['Julafton']],
            'juldagen' => ['2026-12-25', ['Röd dag: Juldagen', 'Flaggdag']],
            'en vanlig dag' => ['2026-10-20', []],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('adultDays')]
    public function test_adults_see_red_days_holidays_and_flag_days(string $date, array $expected)
    {
        $this->assertSame($expected, SpecialDays::on($date));
    }

    public function test_children_see_red_days_and_holidays_by_name_but_no_flag_days()
    {
        $this->assertSame(['Juldagen'], SpecialDays::on('2026-12-25', forChildren: true));
        $this->assertSame(['Valborg'], SpecialDays::on('2026-04-30', forChildren: true));
        $this->assertSame([], SpecialDays::on('2026-10-24', forChildren: true));
    }
}
