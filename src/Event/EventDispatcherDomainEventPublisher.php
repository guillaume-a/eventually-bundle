<?php

namespace Codr\Eventually\Bundle\Event;

use Codr\Eventually\Application\Event\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class EventDispatcherDomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function publish(array $events): void
    {
        foreach ($events as $recorded) {
            $this->dispatcher->dispatch($recorded, $recorded->event::class);
        }
    }
}
