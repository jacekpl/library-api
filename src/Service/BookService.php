<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateBookRequest;
use App\Entity\Book;
use App\Entity\BookEvent;
use App\Exception\BookNotFoundException;
use App\Exception\DuplicateSerialNumberException;
use App\Repository\BookRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;

final class BookService implements BookServiceInterface
{
    public function __construct(
        private readonly BookRepositoryInterface $books,
        private readonly ClockInterface $clock,
    ) {
    }

    public function addBook(CreateBookRequest $request): Book
    {
        if (null !== $this->books->findOneBySerialNumber($request->serialNumber)) {
            throw new DuplicateSerialNumberException($request->serialNumber);
        }

        $book = new Book($request->serialNumber, $request->title, $request->author);
        $this->books->save($book);

        return $book;
    }

    /**
     * @return Book[]
     */
    public function listBooks(): array
    {
        return $this->books->findAllOrderedBySerialNumber();
    }

    public function removeBook(string $serialNumber): void
    {
        $this->books->remove($this->getBook($serialNumber));
    }

    public function borrowBook(string $serialNumber, string $cardNumber): Book
    {
        $book = $this->getBook($serialNumber);
        $book->borrow($cardNumber, $this->clock->now());
        $this->books->save($book);

        return $book;
    }

    public function returnBook(string $serialNumber): Book
    {
        $book = $this->getBook($serialNumber);
        $book->returnToShelf($this->clock->now());
        $this->books->save($book);

        return $book;
    }

    /**
     * @return BookEvent[]
     */
    public function bookHistory(string $serialNumber): array
    {
        return $this->getBook($serialNumber)->events();
    }

    public function getBook(string $serialNumber): Book
    {
        $book = $this->books->findOneBySerialNumber($serialNumber);
        if (null === $book) {
            throw new BookNotFoundException($serialNumber);
        }

        return $book;
    }
}
