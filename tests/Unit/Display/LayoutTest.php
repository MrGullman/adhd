<?php

namespace Tests\Unit\Display;

use App\Display\Layout;
use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
    public function test_a_widget_goes_to_the_first_free_place_at_its_size()
    {
        $taken = [['x' => 0, 'y' => 0, 'w' => 4, 'h' => 6]];

        $this->assertSame(['x' => 4, 'y' => 0, 'w' => 3, 'h' => 4], Layout::place($taken, 3, 4));
    }

    public function test_a_widget_is_made_smaller_when_its_size_does_not_fit()
    {
        // Only a strip one row high is free at the bottom.
        $taken = [['x' => 0, 'y' => 0, 'w' => 12, 'h' => 5]];

        $this->assertSame(['x' => 0, 'y' => 5, 'w' => 3, 'h' => 1], Layout::place($taken, 3, 3));
    }

    public function test_a_full_display_has_no_place()
    {
        $this->assertNull(Layout::place([['x' => 0, 'y' => 0, 'w' => 12, 'h' => 6]], 1, 1));
    }

    public function test_widgets_on_top_of_others_move_and_the_rest_keep_their_place()
    {
        $changes = Layout::arrange([
            ['id' => 1, 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
            ['id' => 2, 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],   // on top of 1
            ['id' => 3, 'x' => 6, 'y' => 0, 'w' => 3, 'h' => 2],   // where 2 would otherwise go
            ['id' => 4, 'x' => 10, 'y' => 5, 'w' => 4, 'h' => 2],  // outside the display
        ]);

        $this->assertArrayNotHasKey(1, $changes);
        $this->assertArrayNotHasKey(3, $changes, 'a widget in the right place keeps it');
        $this->assertSame(['x' => 9, 'y' => 0, 'w' => 3, 'h' => 2], $changes[2]);
        $this->assertSame(['x' => 6, 'y' => 2, 'w' => 4, 'h' => 2], $changes[4]);
    }
}
