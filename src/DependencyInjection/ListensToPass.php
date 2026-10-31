<?php

namespace Codr\Eventually\Bundle\DependencyInjection;

use Codr\Eventually\Application\Attribute\ListensTo;
use LogicException;
use ReflectionClass;
use Reflector;
use Symfony\Component\DependencyInjection\ChildDefinition;

use function sprintf;

final class ListensToPass
{
    public function __invoke(ChildDefinition $definition, ListensTo $listensTo, Reflector $reflector): void
    {
        if (!$reflector instanceof ReflectionClass) {
            throw new LogicException(sprintf('#[%s] only targets classes.', ListensTo::class));
        }

        if (!$reflector->hasMethod($listensTo->method) || !$reflector->getMethod($listensTo->method)->isPublic()) {
            throw new LogicException(sprintf('"%s" listens to "%s" but has no public method "%s".', $reflector->getName(), $listensTo->event, $listensTo->method));
        }

        $definition->addTag('kernel.event_listener', ['event' => $listensTo->event, 'method' => $listensTo->method, 'priority' => $listensTo->priority]);
    }
}
