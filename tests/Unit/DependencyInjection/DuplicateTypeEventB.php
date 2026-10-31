<?php

namespace Codr\Eventually\Bundle\Tests\Unit\DependencyInjection;

use Codr\Eventually\Domain\Aggregate\AggregateId;
use Codr\Eventually\Domain\Attribute\DomainEvent;
use Codr\Eventually\Domain\EventInterface;

#[DomainEvent('test.duplicate')]
final readonly class DuplicateTypeEventB implements EventInterface
{
    public function __construct(private AggregateId $aggregateId)
    {
    }

    public function aggregateId(): AggregateId
    {
        return $this->aggregateId;
    }
}
