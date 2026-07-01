<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookResponse;
use App\Dto\BorrowBookRequest;
use App\Dto\CreateBookRequest;
use App\Service\BookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
final class BookController extends AbstractController
{
    public function __construct(private readonly BookService $books)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $books = array_map(BookResponse::fromEntity(...), $this->books->listBooks());

        return $this->json($books);
    }

    #[Route('/{serialNumber}', methods: ['GET'], requirements: ['serialNumber' => '\d{6}'])]
    public function show(string $serialNumber): JsonResponse
    {
        return $this->json(BookResponse::fromEntity($this->books->getBook($serialNumber)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateBookRequest $request): JsonResponse
    {
        $book = $this->books->addBook($request);

        return $this->json(
            BookResponse::fromEntity($book),
            Response::HTTP_CREATED,
            ['Location' => '/api/books/'.$book->serialNumber()],
        );
    }

    #[Route('/{serialNumber}', methods: ['DELETE'], requirements: ['serialNumber' => '\d{6}'])]
    public function delete(string $serialNumber): JsonResponse
    {
        $this->books->removeBook($serialNumber);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{serialNumber}/borrow', methods: ['POST'], requirements: ['serialNumber' => '\d{6}'])]
    public function borrow(string $serialNumber, #[MapRequestPayload] BorrowBookRequest $request): JsonResponse
    {
        $book = $this->books->borrowBook($serialNumber, $request->cardNumber);

        return $this->json(BookResponse::fromEntity($book));
    }

    #[Route('/{serialNumber}/return', methods: ['POST'], requirements: ['serialNumber' => '\d{6}'])]
    public function returnBook(string $serialNumber): JsonResponse
    {
        $book = $this->books->returnBook($serialNumber);

        return $this->json(BookResponse::fromEntity($book));
    }
}
