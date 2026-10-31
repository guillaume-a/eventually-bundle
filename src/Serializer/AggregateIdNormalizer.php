<?php

namespace Codr\Eventually\Bundle\Serializer;

use Codr\Eventually\Domain\Aggregate\AggregateId;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function is_string;
use function sprintf;

final class AggregateIdNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        if (!$data instanceof AggregateId) {
            throw new InvalidArgumentException(sprintf('Expected instance of %s.', AggregateId::class));
        }

        return $data->toString();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AggregateId;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): AggregateId
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Expected a string value for AggregateId.');
        }

        return AggregateId::fromString($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return AggregateId::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [AggregateId::class => true];
    }
}
