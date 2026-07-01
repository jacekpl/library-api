<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\BookEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BookEventNormalizer implements NormalizerInterface
{
    /**
     * @param BookEvent $data
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'type' => $data->type()->value,
            'cardNumber' => $data->cardNumber(),
            'occurredAt' => $data->occurredAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BookEvent;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [BookEvent::class => true];
    }
}
