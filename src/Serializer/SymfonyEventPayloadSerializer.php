<?php

namespace Codr\Eventually\Bundle\Serializer;

use Codr\Eventually\Doctrine\Serializer\EventPayloadSerializerInterface;
use Codr\Eventually\Domain\EventInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class SymfonyEventPayloadSerializer implements EventPayloadSerializerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function normalize(EventInterface $event): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->normalizer->normalize($event);

        return $payload;
    }

    public function denormalize(array $payload, string $eventClass): EventInterface
    {
        return $this->denormalizer->denormalize($payload, $eventClass);
    }
}
