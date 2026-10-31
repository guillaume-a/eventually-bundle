<?php

namespace Codr\Eventually\Bundle\Tests\Fixture;

use Codr\Eventually\Application\Attribute\ListensTo;
use Codr\Eventually\Application\Event\RecordedEvent;
use Codr\Eventually\Application\ReadModel\ProjectorInterface;
use LogicException;

#[ListensTo(CounterWasStarted::class, 'handle')]
#[ListensTo(CounterWasIncremented::class, 'handle')]
final readonly class CounterProjector implements ProjectorInterface
{
    public function __construct(private DoctrineCounterViewRepository $views)
    {
    }

    public function handle(RecordedEvent $recorded): void
    {
        $id = $recorded->event->aggregateId()->toString();

        if ($recorded->event instanceof CounterWasStarted) {
            if (null === $this->views->find($id)) {
                $this->views->save(new CounterView($id, 0, $recorded->recordedAt, $recorded->recordedAt));
            }

            return;
        }

        $view = $this->views->find($id) ?? throw new LogicException('Counter view missing.');
        $view->increment($recorded->recordedAt);
        $this->views->save($view);
    }

    public function reset(): void
    {
        $this->views->clear();
    }
}
