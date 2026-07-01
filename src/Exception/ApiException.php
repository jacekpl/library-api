<?php

declare(strict_types=1);

namespace App\Exception;

interface ApiException
{
    public function statusCode(): int;

    public function errorCode(): string;
}
