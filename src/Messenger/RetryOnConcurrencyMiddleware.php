<?php

namespace Codr\Eventually\Bundle\Messenger;

use Codr\Eventually\Domain\Exception\ConcurrencyException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Throwable;

use function str_contains;

final class RetryOnConcurrencyMiddleware implements MiddlewareInterface
{
    private int $depth = 0;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly int $maxAttempts,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($this->depth > 0) {
            return $stack->next()->handle($envelope, $stack);
        }

        ++$this->depth;

        try {
            return $this->attempt($envelope, $stack);
        } finally {
            --$this->depth;
        }
    }

    private function attempt(Envelope $envelope, StackInterface $stack): Envelope
    {
        for ($attempt = 1;; ++$attempt) {
            $attemptStack = clone $stack;

            try {
                return $attemptStack->next()->handle($envelope, $attemptStack);
            } catch (Throwable $exception) {
                $conflict = $this->conflictOf($exception);

                if (null === $conflict) {
                    throw $exception;
                }

                if ($attempt >= $this->maxAttempts) {
                    throw $conflict;
                }

                $this->resetClosedManagers();
            }
        }
    }

    private function conflictOf(Throwable $exception): ?ConcurrencyException
    {
        if ($exception instanceof HandlerFailedException) {
            foreach ($exception->getWrappedExceptions() as $wrapped) {
                $conflict = $this->conflictOf($wrapped);

                if (null !== $conflict) {
                    return $conflict;
                }
            }

            return null;
        }

        if ($exception instanceof ConcurrencyException) {
            return $exception;
        }

        if ($exception instanceof UniqueConstraintViolationException && str_contains($exception->getMessage(), 'uniq_aggregate_version')) {
            return new ConcurrencyException('Concurrent write detected on an aggregate stream.', previous: $exception);
        }

        return null;
    }

    private function resetClosedManagers(): void
    {
        foreach ($this->managerRegistry->getManagers() as $name => $manager) {
            if ($manager instanceof EntityManagerInterface && !$manager->isOpen()) {
                $this->managerRegistry->resetManager($name);
            }
        }
    }
}
