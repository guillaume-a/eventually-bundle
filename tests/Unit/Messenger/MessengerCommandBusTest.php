<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Messenger;

use Codr\Eventually\Bundle\Messenger\MessengerCommandBus;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerCommandBusTest extends TestCase
{
    public function testDispatchReturnsHandlerResult(): void
    {
        $command = new stdClass();
        $envelope = new Envelope($command, [new HandledStamp('result', 'handler')]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->with($command)->willReturn($envelope);

        $bus = new MessengerCommandBus($messageBus);

        self::assertSame('result', $bus->dispatch($command));
    }

    public function testDispatchUnwrapsHandlerFailedException(): void
    {
        $command = new stdClass();
        $originalException = new RuntimeException('handler exploded');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())->method('dispatch')->willThrowException(
            new HandlerFailedException(new Envelope($command), [$originalException]),
        );

        $bus = new MessengerCommandBus($messageBus);

        $this->expectExceptionObject($originalException);

        $bus->dispatch($command);
    }
}
