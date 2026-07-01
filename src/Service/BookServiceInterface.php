<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateBookRequest;
use App\Entity\Book;

interface BookServiceInterface
{
    public function addBook(CreateBookRequest $request): Book;

    /**
     * @return Book[]
     */
    public function listBooks(): array;

    public function removeBook(string $serialNumber): void;

    public function borrowBook(string $serialNumber, string $cardNumber): Book;

    public function returnBook(string $serialNumber): Book;

    public function getBook(string $serialNumber): Book;
}
