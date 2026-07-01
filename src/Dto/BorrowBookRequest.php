<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BorrowBookRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{6}$/D', message: 'The card number must be a six-digit number.')]
        public string $cardNumber = '',
    ) {
    }
}
