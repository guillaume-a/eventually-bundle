<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Messenger;

use Codr\Eventually\Bundle\Messenger\MessengerQueryBus;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBusTest extends TestCase
{
    public function testAskReturnsHandlerResult(): void
    {
        $query = new stdClass();
        $envelope = new Envelope($query, [new HandledStamp('result', 'handler')]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->with($query)->willReturn($envelope);

        $bus = new MessengerQueryBus($messageBus);

        self::assertSame('result', $bus->ask($query));
    }

    public function testAskUnwrapsHandlerFailedException(): void
    {
        $query = new stdClass();
        $originalException = new RuntimeException('handler exploded');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willThrowException(
            new HandlerFailedException(new Envelope($query), [$originalException]),
        );

        $bus = new MessengerQueryBus($messageBus);

        $this->expectExceptionObject($originalException);

        $bus->ask($query);
    }
}
