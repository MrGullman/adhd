<?php

namespace App\Enums;

/**
 * What a family's own photo is for. Recipe photos are not bildstöd, so they
 * are only offered when choosing a picture for food. The shared gallery is
 * offered everywhere.
 */
enum PicturePurpose: string
{
    case Support = 'support';
    case Food = 'food';
}
