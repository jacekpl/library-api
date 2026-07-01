<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function jsonRequest(string $method, string $uri, ?array $payload = null): void
    {
        $this->client->request(
            $method,
            $uri,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: null === $payload ? null : json_encode($payload, \JSON_THROW_ON_ERROR),
        );
    }

    protected function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    protected function persistBook(string $serialNumber, string $title = 'A Title', string $author = 'An Author'): Book
    {
        $book = new Book($serialNumber, $title, $author);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($book);
        $em->flush();

        return $book;
    }
}
