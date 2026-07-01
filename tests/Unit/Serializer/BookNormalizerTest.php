<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Entity\Book;
use App\Serializer\BookNormalizer;
use PHPUnit\Framework\TestCase;

final class BookNormalizerTest extends TestCase
{
    private BookNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BookNormalizer();
    }

    public function testItSupportsOnlyBookInstances(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new Book('123456', 'T', 'A')));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testItNormalizesAnAvailableBook(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');

        self::assertSame([
            'serialNumber' => '123456',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'borrowed' => false,
            'borrowedBy' => null,
            'borrowedAt' => null,
        ], $this->normalizer->normalize($book));
    }

    public function testItNormalizesABorrowedBook(): void
    {
        $book = new Book('123456', 'Clean Code', 'Robert C. Martin');
        $book->borrow('654321', new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));

        $data = $this->normalizer->normalize($book);

        self::assertTrue($data['borrowed']);
        self::assertSame('654321', $data['borrowedBy']);
        self::assertSame('2026-07-01T10:00:00+00:00', $data['borrowedAt']);
    }
}
