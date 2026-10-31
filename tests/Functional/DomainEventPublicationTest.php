<?php

namespace Codr\Eventually\Bundle\Tests\Functional;

use Codr\Eventually\Application\Aggregate\EventSourcedAggregateRepository;
use Codr\Eventually\Application\Event\RecordedEvent;
use Codr\Eventually\Bundle\Tests\EventuallyKernelTestCase;
use Codr\Eventually\Bundle\Tests\Fixture\Counter;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasIncremented;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasStarted;
use Codr\Eventually\Bundle\Tests\Fixture\StartCounter;
use Codr\Eventually\Domain\Aggregate\AggregateId;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

use function assert;
use function is_int;
use function is_string;

final class DomainEventPublicationTest extends EventuallyKernelTestCase
{
    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = self::getContainer()->get('event_dispatcher');
        assert($dispatcher instanceof EventDispatcherInterface);
        $this->dispatcher = $dispatcher;
    }

    public function testListenerReceivesTheRecordedEventOnceWithItsMetadata(): void
    {
        $received = [];
        $this->dispatcher->addListener(CounterWasStarted::class, static function (RecordedEvent $recorded) use (&$received): void {
            $received[] = $recorded;
        });
        $requestStack = self::getContainer()->get(RequestStack::class);
        assert($requestStack instanceof RequestStack);
        $request = new Request();
        $request->headers->set('X-Correlation-Id', 'req-123');
        $requestStack->push($request);

        $this->commandBus->dispatch(new StartCounter($this->newId()->toString()));

        self::assertCount(1, $received);
        self::assertInstanceOf(CounterWasStarted::class, $received[0]->event);
        self::assertSame(1, $received[0]->version);
        self::assertSame('actor-1', $received[0]->metadata->actorId);
        self::assertSame('req-123', $received[0]->metadata->correlationId);
        self::assertTrue(Uuid::isValid((string) $received[0]->metadata->causationId));
    }

    public function testCorrelationFallsBackToTheCommandIdWithoutRequestHeader(): void
    {
        $received = [];
        $this->dispatcher->addListener(CounterWasStarted::class, static function (RecordedEvent $recorded) use (&$received): void {
            $received[] = $recorded;
        });

        $this->commandBus->dispatch(new StartCounter($this->newId()->toString()));

        self::assertSame($received[0]->metadata->causationId, $received[0]->metadata->correlationId);
    }

    public function testAThrowingListenerRollsTheCommandBack(): void
    {
        $this->dispatcher->addListener(CounterWasStarted::class, static function (): void {
            throw new RuntimeException('listener failed');
        });
        $id = $this->newId();

        try {
            $this->commandBus->dispatch(new StartCounter($id->toString()));
            self::fail('The listener failure must surface.');
        } catch (RuntimeException $exception) {
            self::assertSame('listener failed', $exception->getMessage());
        }

        $count = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM event_store WHERE aggregate_id = ?', [$id->toString()]);
        self::assertSame(0, is_int($count) ? $count : (int) (is_string($count) ? $count : -1));
    }

    public function testListenersAreCalledInVersionOrder(): void
    {
        $versions = [];
        $listener = static function (RecordedEvent $recorded) use (&$versions): void {
            $versions[] = $recorded->version;
        };
        $this->dispatcher->addListener(CounterWasStarted::class, $listener);
        $this->dispatcher->addListener(CounterWasIncremented::class, $listener);

        $counter = Counter::start($this->newId());
        $counter->increment();
        $counter->increment();
        $repository = self::getContainer()->get(EventSourcedAggregateRepository::class);
        assert($repository instanceof EventSourcedAggregateRepository);
        $repository->save($counter);

        self::assertSame([1, 2, 3], $versions);
    }

    private function newId(): AggregateId
    {
        return AggregateId::fromString(Uuid::v7()->toRfc4122());
    }
}
