<?php

declare(strict_types=1);

namespace App\Entity;

enum BookEventType: string
{
    case Borrowed = 'borrowed';
    case Returned = 'returned';
}
