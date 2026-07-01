<?php

declare(strict_types=1);

namespace App\Exception;

final class BookNotFoundException extends \RuntimeException implements ApiException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('Book with serial number "%s" was not found.', $serialNumber));
    }

    public function statusCode(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'book_not_found';
    }
}
