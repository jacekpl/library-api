<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BorrowBookRequest;
use App\Dto\CreateBookRequest;
use App\Service\BookServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/books')]
final class BookController extends AbstractController
{
    public function __construct(private readonly BookServiceInterface $books)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->books->listBooks());
    }

    #[Route('/{serialNumber}', methods: ['GET'], requirements: ['serialNumber' => '\d{6}'])]
    public function show(string $serialNumber): JsonResponse
    {
        return $this->json($this->books->getBook($serialNumber));
    }

    #[Route('/{serialNumber}/history', methods: ['GET'], requirements: ['serialNumber' => '\d{6}'])]
    public function history(string $serialNumber): JsonResponse
    {
        return $this->json($this->books->bookHistory($serialNumber));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateBookRequest $request): JsonResponse
    {
        $book = $this->books->addBook($request);

        return $this->json(
            $book,
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
        return $this->json($this->books->borrowBook($serialNumber, $request->cardNumber));
    }

    #[Route('/{serialNumber}/return', methods: ['POST'], requirements: ['serialNumber' => '\d{6}'])]
    public function returnBook(string $serialNumber): JsonResponse
    {
        return $this->json($this->books->returnBook($serialNumber));
    }
}
