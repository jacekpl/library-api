<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateBookRequest;
use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotBorrowedException;
use App\Exception\BookNotFoundException;
use App\Exception\DuplicateSerialNumberException;
use App\Service\BookService;
use App\Tests\Double\InMemoryBookRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class BookServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private MockClock $clock;
    private BookService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->clock = new MockClock('2026-07-01 10:00:00');
        $this->service = new BookService($this->books, $this->clock);
    }

    public function testAddingABookStoresIt(): void
    {
        $book = $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));

        self::assertSame('123456', $book->serialNumber());
        self::assertFalse($book->isBorrowed());
        self::assertSame($book, $this->books->findOneBySerialNumber('123456'));
    }

    public function testAddingADuplicateSerialNumberIsRejected(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));

        $this->expectException(DuplicateSerialNumberException::class);

        $this->service->addBook(new CreateBookRequest('123456', 'Other', 'Someone'));
    }

    public function testListingReturnsBooksOrderedBySerialNumber(): void
    {
        $this->service->addBook(new CreateBookRequest('300002', 'Second', 'A'));
        $this->service->addBook(new CreateBookRequest('300001', 'First', 'B'));

        $books = $this->service->listBooks();

        self::assertSame(['300001', '300002'], array_map(static fn ($b) => $b->serialNumber(), $books));
    }

    public function testGettingAMissingBookThrows(): void
    {
        $this->expectException(BookNotFoundException::class);

        $this->service->getBook('999999');
    }

    public function testRemovingABook(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));

        $this->service->removeBook('123456');

        self::assertNull($this->books->findOneBySerialNumber('123456'));
    }

    public function testBorrowingRecordsTheCardAndTheClockTime(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));

        $book = $this->service->borrowBook('123456', '654321');

        self::assertTrue($book->isBorrowed());
        self::assertSame('654321', $book->borrowedByCardNumber());
        self::assertEquals($this->clock->now(), $book->borrowedAt());
    }

    public function testBorrowingAnAlreadyBorrowedBookIsRejected(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));
        $this->service->borrowBook('123456', '654321');

        $this->expectException(BookAlreadyBorrowedException::class);

        $this->service->borrowBook('123456', '111111');
    }

    public function testReturningAnAvailableBookIsRejected(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));

        $this->expectException(BookNotBorrowedException::class);

        $this->service->returnBook('123456');
    }

    public function testReturningABorrowedBookMakesItAvailable(): void
    {
        $this->service->addBook(new CreateBookRequest('123456', 'Clean Code', 'Robert C. Martin'));
        $this->service->borrowBook('123456', '654321');

        $book = $this->service->returnBook('123456');

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->borrowedByCardNumber());
    }
}
