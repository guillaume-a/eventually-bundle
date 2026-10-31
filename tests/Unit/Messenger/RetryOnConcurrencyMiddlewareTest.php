<?php

namespace Codr\Eventually\Bundle\Tests\Unit\Messenger;

use ArrayIterator;
use Codr\Eventually\Bundle\Messenger\RetryOnConcurrencyMiddleware;
use Codr\Eventually\Bundle\Tests\Fixture\FailingMiddleware;
use Codr\Eventually\Domain\Exception\ConcurrencyException;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Throwable;

use function sprintf;

final class RetryOnConcurrencyMiddlewareTest extends TestCase
{
    public function testItRetriesAfterAConcurrencyConflictAndReturnsTheSuccess(): void
    {
        $inner = $this->inner([$this->wrapped(new ConcurrencyException('conflict'))]);

        $envelope = $this->handleThrough($this->middleware(3), $inner);

        self::assertSame(2, $inner->calls);
        self::assertInstanceOf(Envelope::class, $envelope);
    }

    public function testItRetriesAnAggregateVersionUniqueViolation(): void
    {
        $inner = $this->inner([$this->versionViolation()]);

        $this->handleThrough($this->middleware(3), $inner);

        self::assertSame(2, $inner->calls);
    }

    public function testItThrowsTheConflictOnceAttemptsAreExhausted(): void
    {
        $inner = $this->inner([$this->versionViolation(), $this->versionViolation(), $this->versionViolation()]);

        try {
            $this->handleThrough($this->middleware(3), $inner);
            self::fail('The conflict must surface.');
        } catch (ConcurrencyException) {
            self::assertSame(3, $inner->calls);
        }
    }

    public function testItDoesNotRetryOtherFailures(): void
    {
        $failure = new RuntimeException('boom');
        $inner = $this->inner([$failure]);

        try {
            $this->handleThrough($this->middleware(3), $inner);
            self::fail('The failure must surface.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
            self::assertSame(1, $inner->calls);
        }
    }

    public function testItDoesNotRetryOtherUniqueViolations(): void
    {
        $inner = $this->inner([$this->violation('uniq_user_username')]);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->handleThrough($this->middleware(3), $inner);
    }

    public function testItResetsAClosedEntityManagerBeforeRetrying(): void
    {
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('isOpen')->willReturn(false);
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn(['default' => $manager]);
        $registry->expects($this->once())->method('resetManager')->with('default');

        $inner = $this->inner([$this->versionViolation()]);

        $this->handleThrough(new RetryOnConcurrencyMiddleware($registry, 3), $inner);
    }

    public function testNestedDispatchesAreNotRetried(): void
    {
        $middleware = $this->middleware(3);
        $nested = $this->inner([$this->versionViolation()]);

        $outer = new class($middleware, $nested) implements MiddlewareInterface {
            public function __construct(
                private readonly RetryOnConcurrencyMiddleware $middleware,
                private readonly FailingMiddleware $nested,
            ) {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                try {
                    $this->middleware->handle($envelope, new StackMiddleware(new ArrayIterator([$this->middleware, $this->nested])));
                } catch (Throwable) {
                }

                return $envelope;
            }
        };

        $this->handleThrough($middleware, $outer);

        self::assertSame(1, $nested->calls);
    }

    private function handleThrough(RetryOnConcurrencyMiddleware $middleware, MiddlewareInterface $inner): Envelope
    {
        $iterator = new ArrayIterator([$middleware, $inner]);

        return $middleware->handle(new Envelope(new stdClass()), new StackMiddleware($iterator));
    }

    private function middleware(int $maxAttempts): RetryOnConcurrencyMiddleware
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn([]);

        return new RetryOnConcurrencyMiddleware($registry, $maxAttempts);
    }

    /**
     * @param list<Throwable> $failures
     */
    private function inner(array $failures): FailingMiddleware
    {
        return new FailingMiddleware($failures);
    }

    private function wrapped(Throwable $exception): HandlerFailedException
    {
        return new HandlerFailedException(new Envelope(new stdClass()), [$exception]);
    }

    private function versionViolation(): UniqueConstraintViolationException
    {
        return $this->violation('uniq_aggregate_version');
    }

    private function violation(string $constraint): UniqueConstraintViolationException
    {
        return new UniqueConstraintViolationException(
            new class(sprintf('duplicate key value violates unique constraint "%s"', $constraint)) extends Exception implements DriverException {
                public function getSQLState(): string
                {
                    return '23505';
                }
            },
            null,
        );
    }
}
