<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Application\Aggregate\AggregateRepositoryInterface;
use Codr\Eventually\Application\Bus\CommandHandlerInterface;
use Codr\Eventually\Domain\Aggregate\AggregateId;

final readonly class IncrementCounterHandler implements CommandHandlerInterface
{
    public function __construct(private AggregateRepositoryInterface $repository)
    {
    }

    public function __invoke(IncrementCounter $command): void
    {
        $counter = $this->repository->load(Counter::class, AggregateId::fromString($command->id));
        $counter->increment();

        $this->repository->save($counter);
    }
}
