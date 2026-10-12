<?php

namespace App\Enums;

enum StepType: string
{
    case Step = 'step';

    /** "Kolla packningen": shows the day's packing lists (Milestone 6). */
    case Packlist = 'packlist';
}
