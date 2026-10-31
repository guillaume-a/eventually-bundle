<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Domain\Aggregate\AggregateId;
use Codr\Eventually\Domain\Attribute\DomainEvent;
use Codr\Eventually\Domain\EventInterface;

#[DomainEvent('counter_was_started')]
final readonly class CounterWasStarted implements EventInterface
{
    public function __construct(private AggregateId $aggregateId)
    {
    }

    public function aggregateId(): AggregateId
    {
        return $this->aggregateId;
    }
}
