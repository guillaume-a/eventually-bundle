<?php

namespace Codr\Eventually\Bundle\Tests\Functional;

use Codr\Eventually\Application\Bus\CommandBusInterface;
use Codr\Eventually\Application\Bus\QueryBusInterface;
use Codr\Eventually\Bundle\EventuallyBundle;
use Codr\Eventually\Bundle\Messenger\MessengerCommandBus;
use Codr\Eventually\Bundle\Messenger\MessengerQueryBus;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasIncremented;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasStarted;
use Codr\Eventually\Bundle\Tests\Kernel;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BundleBootTest extends KernelTestCase
{
    public function testItBootsAndExposesBothBuses(): void
    {
        $container = self::getContainer();

        self::assertInstanceOf(MessengerCommandBus::class, $container->get(CommandBusInterface::class));
        $queryBus = $container->get(QueryBusInterface::class);
        self::assertInstanceOf(MessengerQueryBus::class, $queryBus);
        self::assertTrue(self::getContainer()->has('command.bus'));
        self::assertTrue(self::getContainer()->has('query.bus'));
        self::assertInstanceOf(EventuallyBundle::class, self::$kernel?->getBundle('EventuallyBundle'));
        self::assertSame(20, $container->getParameter('eventually.snapshot_frequency'));
        self::assertSame(3, $container->getParameter('eventually.command_retry.max_attempts'));
    }

    public function testItBuildsTheEventTypeMapFromTaggedEvents(): void
    {
        self::assertSame(
            [
                'counter_was_incremented' => ['class' => CounterWasIncremented::class, 'revision' => 1],
                'counter_was_started' => ['class' => CounterWasStarted::class, 'revision' => 1],
            ],
            self::getContainer()->getParameter('eventually.event_types'),
        );
    }

    public function testAnEventWithoutTheDomainEventAttributeFailsTheContainerBuild(): void
    {
        $kernel = new Kernel('invalid_event', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/MissingAttributeEvent.*DomainEvent/');

        $kernel->boot();
    }
}
