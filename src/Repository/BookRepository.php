<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function findOneBySerialNumber(string $serialNumber): ?Book
    {
        return $this->findOneBy(['serialNumber' => $serialNumber]);
    }

    /**
     * @return Book[]
     */
    public function findAllOrderedBySerialNumber(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.serialNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
