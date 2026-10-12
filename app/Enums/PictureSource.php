<?php

namespace App\Enums;

enum PictureSource: string
{
    /** In the shared gallery, uploaded by an admin. */
    case Gallery = 'gallery';

    /** The family's own photo. */
    case Upload = 'upload';
}
