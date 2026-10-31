<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Serializer;

use Codr\Eventually\Bundle\Serializer\AggregateIdNormalizer;
use Codr\Eventually\Domain\Aggregate\AggregateId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function sprintf;

final class AggregateIdNormalizerTest extends TestCase
{
    private AggregateIdNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new AggregateIdNormalizer();
    }

    public function testNormalizeReturnsStringValueOfAggregateId(): void
    {
        $aggregateId = AggregateId::fromString('0198f1e0-1234-7abc-8def-000000000001');

        self::assertSame('0198f1e0-1234-7abc-8def-000000000001', $this->normalizer->normalize($aggregateId));
    }

    public function testNormalizeThrowsWhenDataIsNotAggregateId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Expected instance of %s.', AggregateId::class));

        $this->normalizer->normalize('not-an-aggregate-id');
    }

    public function testSupportsNormalizationOnlyForAggregateId(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(AggregateId::fromString('id')));
        self::assertFalse($this->normalizer->supportsNormalization('id'));
    }

    public function testDenormalizeReturnsAggregateIdFromString(): void
    {
        $aggregateId = $this->normalizer->denormalize('0198f1e0-1234-7abc-8def-000000000001', AggregateId::class);

        self::assertTrue($aggregateId->equals(AggregateId::fromString('0198f1e0-1234-7abc-8def-000000000001')));
    }

    public function testDenormalizeThrowsWhenDataIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string value for AggregateId.');

        $this->normalizer->denormalize(['not', 'a', 'string'], AggregateId::class);
    }

    public function testSupportsDenormalizationOnlyForAggregateIdType(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization('any-data', AggregateId::class));
        self::assertFalse($this->normalizer->supportsDenormalization('any-data', 'string'));
    }

    public function testGetSupportedTypesReturnsAggregateIdMapping(): void
    {
        self::assertSame([AggregateId::class => true], $this->normalizer->getSupportedTypes(null));
    }
}
