<?php

namespace Codr\Eventually\Bundle\Tests\Unit\DependencyInjection;

use Codr\Eventually\Domain\Aggregate\AggregateId;
use Codr\Eventually\Domain\EventInterface;

final readonly class MissingAttributeEvent implements EventInterface
{
    public function __construct(private AggregateId $aggregateId)
    {
    }

    public function aggregateId(): AggregateId
    {
        return $this->aggregateId;
    }
}
