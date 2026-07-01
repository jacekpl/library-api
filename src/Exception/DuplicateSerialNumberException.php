<?php

declare(strict_types=1);

namespace App\Exception;

final class DuplicateSerialNumberException extends \RuntimeException implements ApiException
{
    public function __construct(string $serialNumber)
    {
        parent::__construct(sprintf('A book with serial number "%s" already exists.', $serialNumber));
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'duplicate_serial_number';
    }
}
