<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class BookApiTest extends ApiTestCase
{
    public function testAddingABookReturnsItAsCreated(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'serialNumber' => '100001',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('Location', '/api/books/100001');
        $data = $this->responseData();
        self::assertSame('100001', $data['serialNumber']);
        self::assertSame('Clean Code', $data['title']);
        self::assertSame('Robert C. Martin', $data['author']);
        self::assertFalse($data['borrowed']);
        self::assertNull($data['borrowedBy']);
        self::assertNull($data['borrowedAt']);

        $this->jsonRequest('GET', '/api/books/100001');
        self::assertResponseIsSuccessful();
        self::assertSame('Clean Code', $this->responseData()['title']);
    }

    public function testGettingASingleBook(): void
    {
        $this->persistBook('200001', 'Refactoring', 'Martin Fowler');

        $this->jsonRequest('GET', '/api/books/200001');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('200001', $data['serialNumber']);
        self::assertSame('Refactoring', $data['title']);
        self::assertSame('Martin Fowler', $data['author']);
    }

    public function testGettingAMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('GET', '/api/books/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame('book_not_found', $this->responseData()['error']['code']);
    }

    public function testAddingABookWithADuplicateSerialNumberIsRejected(): void
    {
        $this->persistBook('100002');

        $this->jsonRequest('POST', '/api/books', [
            'serialNumber' => '100002',
            'title' => 'Another Book',
            'author' => 'Someone',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame('duplicate_serial_number', $this->responseData()['error']['code']);
    }

    #[DataProvider('invalidBookPayloads')]
    public function testAddingABookWithInvalidDataIsRejected(array $payload, string $invalidField): void
    {
        $this->jsonRequest('POST', '/api/books', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $this->responseData();
        self::assertSame('validation_failed', $data['error']['code']);
        $fields = array_column($data['error']['violations'], 'field');
        self::assertContains($invalidField, $fields);
    }

    public static function invalidBookPayloads(): iterable
    {
        yield 'serial number too short' => [
            ['serialNumber' => '123', 'title' => 'T', 'author' => 'A'],
            'serialNumber',
        ];
        yield 'serial number not numeric' => [
            ['serialNumber' => 'ABCDEF', 'title' => 'T', 'author' => 'A'],
            'serialNumber',
        ];
        yield 'blank title' => [
            ['serialNumber' => '123456', 'title' => '', 'author' => 'A'],
            'title',
        ];
        yield 'blank author' => [
            ['serialNumber' => '123456', 'title' => 'T', 'author' => ''],
            'author',
        ];
    }

    public function testListingBooksReturnsThemOrderedBySerialNumber(): void
    {
        $this->persistBook('300002', 'Second');
        $this->persistBook('300001', 'First');

        $this->jsonRequest('GET', '/api/books');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(2, $data);
        self::assertSame('300001', $data[0]['serialNumber']);
        self::assertSame('300002', $data[1]['serialNumber']);
    }

    public function testDeletingABookRemovesIt(): void
    {
        $this->persistBook('400001');

        $this->jsonRequest('DELETE', '/api/books/400001');
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->jsonRequest('GET', '/api/books');
        self::assertCount(0, $this->responseData());
    }

    public function testDeletingAMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('DELETE', '/api/books/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame('book_not_found', $this->responseData()['error']['code']);
    }

    public function testBorrowingAnAvailableBook(): void
    {
        $this->persistBook('500001');

        $this->jsonRequest('POST', '/api/books/500001/borrow', ['cardNumber' => '654321']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertTrue($data['borrowed']);
        self::assertSame('654321', $data['borrowedBy']);
        self::assertNotNull($data['borrowedAt']);
    }

    public function testBorrowingAnAlreadyBorrowedBookIsRejected(): void
    {
        $this->persistBook('500002');
        $this->jsonRequest('POST', '/api/books/500002/borrow', ['cardNumber' => '654321']);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('POST', '/api/books/500002/borrow', ['cardNumber' => '111111']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame('book_already_borrowed', $this->responseData()['error']['code']);
    }

    public function testBorrowingWithAnInvalidCardNumberIsRejected(): void
    {
        $this->persistBook('500003');

        $this->jsonRequest('POST', '/api/books/500003/borrow', ['cardNumber' => '12']);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('validation_failed', $this->responseData()['error']['code']);
    }

    public function testBorrowingAMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('POST', '/api/books/999999/borrow', ['cardNumber' => '654321']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testReturningABorrowedBookMakesItAvailable(): void
    {
        $this->persistBook('600001');
        $this->jsonRequest('POST', '/api/books/600001/borrow', ['cardNumber' => '654321']);
        self::assertResponseIsSuccessful();

        $this->jsonRequest('POST', '/api/books/600001/return');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertFalse($data['borrowed']);
        self::assertNull($data['borrowedBy']);
        self::assertNull($data['borrowedAt']);

        $this->jsonRequest('GET', '/api/books/600001');
        self::assertFalse($this->responseData()['borrowed']);
    }

    public function testReturningAnAvailableBookIsRejected(): void
    {
        $this->persistBook('600002');

        $this->jsonRequest('POST', '/api/books/600002/return');

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertSame('book_not_borrowed', $this->responseData()['error']['code']);
    }

    public function testReturningAMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('POST', '/api/books/999999/return');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testHistoryIsEmptyForANeverBorrowedBook(): void
    {
        $this->persistBook('700001');

        $this->jsonRequest('GET', '/api/books/700001/history');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responseData());
    }

    public function testHistoryRecordsBorrowAndReturnEvents(): void
    {
        $this->persistBook('700002');
        $this->jsonRequest('POST', '/api/books/700002/borrow', ['cardNumber' => '654321']);
        $this->jsonRequest('POST', '/api/books/700002/return');

        $this->jsonRequest('GET', '/api/books/700002/history');

        self::assertResponseIsSuccessful();
        $history = $this->responseData();
        self::assertCount(2, $history);
        self::assertSame('borrowed', $history[0]['type']);
        self::assertSame('654321', $history[0]['cardNumber']);
        self::assertNotNull($history[0]['occurredAt']);
        self::assertSame('returned', $history[1]['type']);
        self::assertSame('654321', $history[1]['cardNumber']);
    }

    public function testHistoryOfAMissingBookReturnsNotFound(): void
    {
        $this->jsonRequest('GET', '/api/books/999999/history');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertSame('book_not_found', $this->responseData()['error']['code']);
    }

    public function testDeletingABookAlsoRemovesItsHistory(): void
    {
        $this->persistBook('700003');
        $this->jsonRequest('POST', '/api/books/700003/borrow', ['cardNumber' => '654321']);

        $this->jsonRequest('DELETE', '/api/books/700003');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
