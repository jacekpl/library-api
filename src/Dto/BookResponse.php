<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Book;

final class BookResponse
{
    public function __construct(
        public string $serialNumber,
        public string $title,
        public string $author,
        public bool $borrowed,
        public ?string $borrowedBy,
        public ?string $borrowedAt,
    ) {
    }

    public static function fromEntity(Book $book): self
    {
        return new self(
            $book->serialNumber(),
            $book->title(),
            $book->author(),
            $book->isBorrowed(),
            $book->borrowedByCardNumber(),
            $book->borrowedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
