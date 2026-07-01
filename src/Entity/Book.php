<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\BookAlreadyBorrowedException;
use App\Exception\BookNotBorrowedException;
use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'book')]
#[ORM\UniqueConstraint(name: 'uniq_book_serial_number', columns: ['serial_number'])]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(name: 'serial_number', length: 6)]
    private string $serialNumber;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255)]
    private string $author;

    #[ORM\Column(name: 'borrowed_by_card_number', length: 6, nullable: true)]
    private ?string $borrowedByCardNumber = null;

    #[ORM\Column(name: 'borrowed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $borrowedAt = null;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    public function __construct(string $serialNumber, string $title, string $author)
    {
        $this->id = Uuid::v7();
        $this->serialNumber = $serialNumber;
        $this->title = $title;
        $this->author = $author;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function serialNumber(): string
    {
        return $this->serialNumber;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function isBorrowed(): bool
    {
        return null !== $this->borrowedByCardNumber;
    }

    public function borrowedByCardNumber(): ?string
    {
        return $this->borrowedByCardNumber;
    }

    public function borrowedAt(): ?\DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function borrow(string $cardNumber, \DateTimeImmutable $borrowedAt): void
    {
        if ($this->isBorrowed()) {
            throw new BookAlreadyBorrowedException($this->serialNumber);
        }

        $this->borrowedByCardNumber = $cardNumber;
        $this->borrowedAt = $borrowedAt;
    }

    public function returnToShelf(): void
    {
        if (!$this->isBorrowed()) {
            throw new BookNotBorrowedException($this->serialNumber);
        }

        $this->borrowedByCardNumber = null;
        $this->borrowedAt = null;
    }
}
