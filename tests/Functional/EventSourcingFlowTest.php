<?php

namespace Codr\Eventually\Bundle\Tests\Functional;

use Codr\Eventually\Application\Aggregate\AggregateRepositoryInterface;
use Codr\Eventually\Bundle\Tests\EventuallyKernelTestCase;
use Codr\Eventually\Bundle\Tests\Fixture\CountCounters;
use Codr\Eventually\Bundle\Tests\Fixture\Counter;
use Codr\Eventually\Bundle\Tests\Fixture\IncrementCounter;
use Codr\Eventually\Bundle\Tests\Fixture\StartCounter;
use Codr\Eventually\Domain\Aggregate\AggregateId;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;

use function assert;
use function is_int;
use function is_string;

final class EventSourcingFlowTest extends EventuallyKernelTestCase
{
    private const string VIEW_ROWS = 'SELECT id, count, created_at, updated_at FROM view_counter ORDER BY position';

    public function testCommandsAreReplayedFromSnapshotAndEventsAndProjected(): void
    {
        $id = $this->newId();
        $this->commandBus->dispatch(new StartCounter($id));
        for ($i = 0; $i < 25; ++$i) {
            $this->clock->sleep(1);
            $this->commandBus->dispatch(new IncrementCounter($id));
        }

        $repository = self::getContainer()->get(AggregateRepositoryInterface::class);
        assert($repository instanceof AggregateRepositoryInterface);
        $counter = $repository->load(Counter::class, AggregateId::fromString($id));

        self::assertSame(25, $counter->count());
        self::assertSame(26, $this->scalar('SELECT COUNT(*) FROM event_store'));
        self::assertSame(1, $this->scalar('SELECT COUNT(*) FROM aggregate_snapshot'));
        self::assertSame(25, $this->scalar('SELECT count FROM view_counter'));
        self::assertSame(1, $this->queryBus->ask(new CountCounters()));
    }

    public function testRebuildReproducesTheViewRowsFromTheEventStoreAlone(): void
    {
        $first = $this->newId();
        $second = $this->newId();
        foreach ([$first, $second] as $id) {
            $this->commandBus->dispatch(new StartCounter($id));
            $this->clock->sleep(60);
            $this->commandBus->dispatch(new IncrementCounter($id));
        }
        $before = $this->entityManager->getConnection()->fetchAllAssociative(self::VIEW_ROWS);
        self::assertCount(2, $before);

        $this->entityManager->getConnection()->executeStatement('UPDATE view_counter SET count = 999');

        $tester = new CommandTester(new Application(self::bootKernel())->find('app:read-models:rebuild'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertSame($before, $this->entityManager->getConnection()->fetchAllAssociative(self::VIEW_ROWS));
    }

    private function newId(): string
    {
        return AggregateId::fromString(Uuid::v7()->toRfc4122())->toString();
    }

    private function scalar(string $sql): int
    {
        $value = $this->entityManager->getConnection()->fetchOne($sql);
        assert(is_int($value) || is_string($value));

        return (int) $value;
    }
}
