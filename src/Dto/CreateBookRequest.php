<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateBookRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d{6}$/D', message: 'The serial number must be a six-digit number.')]
        public string $serialNumber = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title = '',

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $author = '',
    ) {
    }
}
