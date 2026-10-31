<?php

namespace Codr\Eventually\Bundle\Tests\Functional;

use Codr\Eventually\Application\Event\RecordedEvent;
use Codr\Eventually\Bundle\Tests\EventuallyKernelTestCase;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasStarted;
use Codr\Eventually\Bundle\Tests\Fixture\StartCounter;
use Codr\Eventually\Domain\Aggregate\AggregateId;
use Codr\Eventually\Domain\Exception\ConcurrencyException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

use function assert;

final class ConcurrencyRetryTest extends EventuallyKernelTestCase
{
    public function testAWriteLosingTheVersionRaceIsRetriedThreeTimesThenSurfaces(): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        assert($dispatcher instanceof EventDispatcherInterface);
        $attempts = 0;
        $dispatcher->addListener(CounterWasStarted::class, static function (RecordedEvent $recorded) use (&$attempts): void {
            ++$attempts;
        });

        $id = AggregateId::fromString(Uuid::v7()->toRfc4122())->toString();
        $this->commandBus->dispatch(new StartCounter($id));
        self::assertSame(1, $attempts);

        try {
            $this->commandBus->dispatch(new StartCounter($id));
            self::fail('The duplicate stream must surface a conflict.');
        } catch (ConcurrencyException) {
            self::assertSame(4, $attempts);
        }
    }
}
