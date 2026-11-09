<?php

namespace App\Enums;

/**
 * Whether a line in the bag can still be ordered as it is.
 */
enum CartLineStatus: string
{
    /** Ready to order. */
    case Available = 'available';

    /** Fewer are left than were in the bag, so the quantity was lowered. */
    case Reduced = 'reduced';

    /** Sold out or no longer sold. Left out of the totals until removed. */
    case Unavailable = 'unavailable';
}
