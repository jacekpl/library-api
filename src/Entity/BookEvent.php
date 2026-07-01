<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'book_event')]
class BookEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column(enumType: BookEventType::class)]
    private BookEventType $type;

    #[ORM\Column(name: 'card_number', length: 6)]
    private string $cardNumber;

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    private function __construct(Book $book, BookEventType $type, string $cardNumber, \DateTimeImmutable $occurredAt)
    {
        $this->id = Uuid::v7();
        $this->book = $book;
        $this->type = $type;
        $this->cardNumber = $cardNumber;
        $this->occurredAt = $occurredAt;
    }

    public static function borrowed(Book $book, string $cardNumber, \DateTimeImmutable $occurredAt): self
    {
        return new self($book, BookEventType::Borrowed, $cardNumber, $occurredAt);
    }

    public static function returned(Book $book, string $cardNumber, \DateTimeImmutable $occurredAt): self
    {
        return new self($book, BookEventType::Returned, $cardNumber, $occurredAt);
    }

    public function type(): BookEventType
    {
        return $this->type;
    }

    public function cardNumber(): string
    {
        return $this->cardNumber;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
