<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Book;
use App\Entity\BookEventType;
use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotBorrowedException;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testANewBookIsAvailable(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');

        self::assertSame('123456', $book->serialNumber());
        self::assertSame('Clean Code', $book->title());
        self::assertSame('Robert C. Martin', $book->author());
        self::assertFalse($book->isBorrowed());
        self::assertNull($book->borrowedByCardNumber());
        self::assertNull($book->borrowedAt());
    }

    public function testBorrowingMarksTheBookAsBorrowed(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        $borrowedAt = new \DateTimeImmutable('2026-07-01T10:00:00+00:00');

        $book->borrow('654321', $borrowedAt);

        self::assertTrue($book->isBorrowed());
        self::assertSame('654321', $book->borrowedByCardNumber());
        self::assertEquals($borrowedAt, $book->borrowedAt());
    }

    public function testAnAlreadyBorrowedBookCannotBeBorrowedAgain(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        $book->borrow('654321', new \DateTimeImmutable());

        $this->expectException(BookAlreadyBorrowedException::class);

        $book->borrow('111111', new \DateTimeImmutable());
    }

    public function testReturningMakesTheBookAvailableAgain(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        $book->borrow('654321', new \DateTimeImmutable());

        $book->returnToShelf(new \DateTimeImmutable());

        self::assertFalse($book->isBorrowed());
        self::assertNull($book->borrowedByCardNumber());
        self::assertNull($book->borrowedAt());
    }

    public function testAnAvailableBookCannotBeReturned(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');

        $this->expectException(BookNotBorrowedException::class);

        $book->returnToShelf(new \DateTimeImmutable());
    }

    public function testABorrowedBookCanBeBorrowedAgainAfterBeingReturned(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        $book->borrow('654321', new \DateTimeImmutable());
        $book->returnToShelf(new \DateTimeImmutable());

        $book->borrow('111111', new \DateTimeImmutable('2026-07-02T09:00:00+00:00'));

        self::assertTrue($book->isBorrowed());
        self::assertSame('111111', $book->borrowedByCardNumber());
    }

    public function testBorrowingAndReturningIsRecordedAsImmutableHistory(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        self::assertSame([], $book->events());

        $book->borrow('654321', new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));
        $book->returnToShelf(new \DateTimeImmutable('2026-07-05T12:00:00+00:00'));

        $events = $book->events();
        self::assertCount(2, $events);

        self::assertSame(BookEventType::Borrowed, $events[0]->type());
        self::assertSame('654321', $events[0]->cardNumber());
        self::assertEquals(new \DateTimeImmutable('2026-07-01T10:00:00+00:00'), $events[0]->occurredAt());

        self::assertSame(BookEventType::Returned, $events[1]->type());
        self::assertSame('654321', $events[1]->cardNumber());
        self::assertEquals(new \DateTimeImmutable('2026-07-05T12:00:00+00:00'), $events[1]->occurredAt());
    }
}
