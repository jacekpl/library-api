<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer;

use App\Entity\Book;
use App\Serializer\BookEventNormalizer;
use PHPUnit\Framework\TestCase;

final class BookEventNormalizerTest extends TestCase
{
    private BookEventNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BookEventNormalizer();
    }

    public function testItSupportsOnlyBookEventInstances(): void
    {
        $book = new Book('123456', 'T', 'A');
        $book->borrow('654321', new \DateTimeImmutable());

        self::assertTrue($this->normalizer->supportsNormalization($book->events()[0]));
        self::assertFalse($this->normalizer->supportsNormalization($book));
    }

    public function testItNormalizesAnEvent(): void
    {
        $book = new Book('123456', 'T', 'A');
        $book->borrow('654321', new \DateTimeImmutable('2026-07-01T10:00:00+00:00'));

        self::assertSame([
            'type' => 'borrowed',
            'cardNumber' => '654321',
            'occurredAt' => '2026-07-01T10:00:00+00:00',
        ], $this->normalizer->normalize($book->events()[0]));
    }
}
