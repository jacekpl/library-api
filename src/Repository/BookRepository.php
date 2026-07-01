<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;

final class BookRepository implements BookRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findOneBySerialNumber(string $serialNumber): ?Book
    {
        return $this->em->getRepository(Book::class)->findOneBy(['serialNumber' => $serialNumber]);
    }

    public function findAllOrderedBySerialNumber(): array
    {
        return $this->em->getRepository(Book::class)->findBy([], ['serialNumber' => 'ASC']);
    }

    public function save(Book $book): void
    {
        $this->em->persist($book);
        $this->em->flush();
    }

    public function remove(Book $book): void
    {
        $this->em->remove($book);
        $this->em->flush();
    }
}
