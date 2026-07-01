<?php

declare(strict_types=1);

namespace App\Exception;

final class BookNotBorrowedException extends \RuntimeException implements ApiException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book with serial number "%s" is not borrowed.', $serialNumber));
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'book_not_borrowed';
    }
}
