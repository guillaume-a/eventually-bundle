<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Throwable;

use function array_shift;

final class FailingMiddleware implements MiddlewareInterface
{
    public int $calls = 0;

    /**
     * @param list<Throwable> $failures
     */
    public function __construct(
        private array $failures,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        ++$this->calls;
        $failure = array_shift($this->failures);

        if (null !== $failure) {
            throw $failure;
        }

        return $envelope;
    }
}
