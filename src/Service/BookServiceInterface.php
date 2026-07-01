<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateBookRequest;
use App\Entity\Book;
use App\Entity\BookEvent;

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

    /**
     * @return BookEvent[]
     */
    public function bookHistory(string $serialNumber): array;
}
