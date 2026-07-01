<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Book;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BookNormalizer implements NormalizerInterface
{
    /**
     * @param Book $data
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'serialNumber' => $data->serialNumber(),
            'title' => $data->title(),
            'author' => $data->author(),
            'borrowed' => $data->isBorrowed(),
            'borrowedBy' => $data->borrowedByCardNumber(),
            'borrowedAt' => $data->borrowedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Book;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Book::class => true];
    }
}
