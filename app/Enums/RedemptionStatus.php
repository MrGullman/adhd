<?php

namespace App\Enums;

enum RedemptionStatus: string
{
    /** The child has wished for it. */
    case Requested = 'requested';

    /** A parent said yes: the points are used. */
    case Approved = 'approved';

    /** A parent said "inte nu". No points are used. */
    case Declined = 'declined';
}
