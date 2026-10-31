<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Application\Aggregate\AggregateRepositoryInterface;
use Codr\Eventually\Application\Bus\CommandHandlerInterface;
use Codr\Eventually\Domain\Aggregate\AggregateId;

final readonly class StartCounterHandler implements CommandHandlerInterface
{
    public function __construct(private AggregateRepositoryInterface $repository)
    {
    }

    public function __invoke(StartCounter $command): void
    {
        $this->repository->save(Counter::start(AggregateId::fromString($command->id)));
    }
}
