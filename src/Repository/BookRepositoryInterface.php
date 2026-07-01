<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;

interface BookRepositoryInterface
{
    public function findOneBySerialNumber(string $serialNumber): ?Book;

    /**
     * @return Book[]
     */
    public function findAllOrderedBySerialNumber(): array;

    public function save(Book $book): void;

    public function remove(Book $book): void;
}
