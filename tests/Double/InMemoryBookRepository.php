<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Entity\Book;
use App\Repository\BookRepositoryInterface;

final class InMemoryBookRepository implements BookRepositoryInterface
{
    /**
     * @var array<string, Book>
     */
    private array $books = [];

    public function findOneBySerialNumber(string $serialNumber): ?Book
    {
        return $this->books[$serialNumber] ?? null;
    }

    public function findAllOrderedBySerialNumber(): array
    {
        $books = array_values($this->books);
        usort($books, static fn (Book $a, Book $b): int => $a->serialNumber() <=> $b->serialNumber());

        return $books;
    }

    public function save(Book $book): void
    {
        $this->books[$book->serialNumber()] = $book;
    }

    public function remove(Book $book): void
    {
        unset($this->books[$book->serialNumber()]);
    }
}
