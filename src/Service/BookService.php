<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateBookRequest;
use App\Entity\Book;
use App\Exception\BookNotFoundException;
use App\Exception\DuplicateSerialNumberException;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

final class BookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function addBook(CreateBookRequest $request): Book
    {
        if (null !== $this->books->findOneBySerialNumber($request->serialNumber)) {
            throw new DuplicateSerialNumberException($request->serialNumber);
        }

        $book = new Book($request->serialNumber, $request->title, $request->author);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

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
        $book = $this->getBook($serialNumber);

        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }

    public function borrowBook(string $serialNumber, string $cardNumber): Book
    {
        $book = $this->getBook($serialNumber);
        $book->borrow($cardNumber, $this->clock->now());
        $this->entityManager->flush();

        return $book;
    }

    public function returnBook(string $serialNumber): Book
    {
        $book = $this->getBook($serialNumber);
        $book->returnToShelf();
        $this->entityManager->flush();

        return $book;
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
