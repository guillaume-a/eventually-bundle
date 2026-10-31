<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Application\Bus\QueryHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CountCountersHandler implements QueryHandlerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(CountCounters $query): int
    {
        return $this->entityManager->getRepository(CounterView::class)->count([]);
    }
}
