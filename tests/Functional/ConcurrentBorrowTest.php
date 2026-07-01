<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Book;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ConcurrentBorrowTest extends KernelTestCase
{
    public function testConcurrentBorrowOfTheSameBookIsRejected(): void
    {
        self::bootKernel();
        $em1 = self::getContainer()->get(EntityManagerInterface::class);

        $book = new Book('700001', 'Domain-Driven Design', 'Eric Evans');
        $em1->persist($book);
        $em1->flush();
        $id = $book->id();
        $em1->clear();

        $em2 = new EntityManager($em1->getConnection(), $em1->getConfiguration());

        $first = $em1->find(Book::class, $id);
        $second = $em2->find(Book::class, $id);

        $first->borrow('111111', new \DateTimeImmutable());
        $em1->flush();

        $second->borrow('222222', new \DateTimeImmutable());

        $this->expectException(OptimisticLockException::class);
        $em2->flush();
    }
}
