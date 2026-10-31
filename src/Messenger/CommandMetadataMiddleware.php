<?php

namespace Codr\Eventually\Bundle\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class CommandMetadataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CommandMetadataContext $context,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var Envelope $handled */
        $handled = $this->context->around(static fn (): Envelope => $stack->next()->handle($envelope, $stack));

        return $handled;
    }
}
