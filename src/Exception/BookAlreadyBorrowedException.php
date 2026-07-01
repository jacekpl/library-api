<?php

declare(strict_types=1);

namespace App\Exception;

final class BookAlreadyBorrowedException extends \RuntimeException implements ApiException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book with serial number "%s" is already borrowed.', $serialNumber));
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'book_already_borrowed';
    }
}
