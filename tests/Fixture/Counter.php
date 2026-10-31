<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Domain\Aggregate\AggregateId;
use Codr\Eventually\Domain\Aggregate\AggregateRoot;
use Codr\Eventually\Domain\Attribute\Aggregate;
use Codr\Eventually\Domain\EventInterface;
use LogicException;

use function is_int;
use function is_string;

#[Aggregate('counter')]
final class Counter extends AggregateRoot
{
    private ?AggregateId $id = null;
    private int $count = 0;

    public static function start(AggregateId $id): self
    {
        $counter = new self();
        $counter->recordEvent(new CounterWasStarted($id));

        return $counter;
    }

    public function increment(): void
    {
        $this->recordEvent(new CounterWasIncremented($this->id()));
    }

    public function count(): int
    {
        return $this->count;
    }

    public function id(): AggregateId
    {
        return $this->id ?? throw new LogicException('Counter not started.');
    }

    protected function apply(EventInterface $event): void
    {
        if ($event instanceof CounterWasStarted) {
            $this->id = $event->aggregateId();

            return;
        }

        ++$this->count;
    }

    protected function captureState(): array
    {
        return ['id' => $this->id()->toString(), 'count' => $this->count];
    }

    protected function restoreState(array $state): void
    {
        $id = $state['id'] ?? null;
        $count = $state['count'] ?? null;

        $this->id = AggregateId::fromString(is_string($id) ? $id : throw new LogicException('Invalid snapshot.'));
        $this->count = is_int($count) ? $count : 0;
    }
}
