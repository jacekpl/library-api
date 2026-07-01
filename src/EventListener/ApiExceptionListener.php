<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\ApiException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 64)]
final class ApiExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        $validationFailure = $this->findValidationFailure($exception);
        if (null !== $validationFailure) {
            $event->setResponse($this->validationResponse($validationFailure));

            return;
        }

        if ($exception instanceof ApiException) {
            $event->setResponse($this->errorResponse(
                $exception->statusCode(),
                $exception->errorCode(),
                $exception->getMessage(),
            ));

            return;
        }

        if ($exception instanceof OptimisticLockException) {
            $event->setResponse($this->errorResponse(
                409,
                'concurrent_modification',
                'The book was modified by another request. Please retry.',
            ));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse($this->errorResponse(
                $exception->getStatusCode(),
                'http_error',
                $exception->getMessage(),
                $exception->getHeaders(),
            ));

            return;
        }

        $this->logger->error('Unhandled API exception.', ['exception' => $exception]);

        $event->setResponse($this->errorResponse(
            500,
            'internal_error',
            $this->debug ? $exception->getMessage() : 'Internal server error.',
        ));
    }

    private function findValidationFailure(\Throwable $exception): ?ValidationFailedException
    {
        for ($current = $exception; null !== $current; $current = $current->getPrevious()) {
            if ($current instanceof ValidationFailedException) {
                return $current;
            }
        }

        return null;
    }

    private function validationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'The submitted data is invalid.',
                'violations' => $violations,
            ],
        ], 422);
    }

    /**
     * @param array<string, string> $headers
     */
    private function errorResponse(int $status, string $code, string $message, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status, $headers);
    }
}
