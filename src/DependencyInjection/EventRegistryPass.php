<?php

namespace Codr\Eventually\Bundle\DependencyInjection;

use Codr\Eventually\Domain\Attribute\DomainEvent;
use Codr\Eventually\Domain\EventInterface;
use LogicException;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function sprintf;

final class EventRegistryPass implements CompilerPassInterface
{
    public const string PARAMETER = 'eventually.event_types';

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, array{class: class-string<EventInterface>, revision: int}> $map */
        $map = [];

        foreach (array_keys($container->findTaggedServiceIds(EventInterface::class)) as $id) {
            /** @var class-string<EventInterface> $class */
            $class = $container->getDefinition($id)->getClass() ?? $id;
            $attributes = new ReflectionClass($class)->getAttributes(DomainEvent::class);

            if ([] === $attributes) {
                throw new LogicException(sprintf('Event "%s" must declare the #[%s] attribute.', $class, DomainEvent::class));
            }

            $domainEvent = $attributes[0]->newInstance();

            if (isset($map[$domainEvent->type])) {
                throw new LogicException(sprintf('Event type "%s" is declared by both "%s" and "%s".', $domainEvent->type, $map[$domainEvent->type]['class'], $class));
            }

            $map[$domainEvent->type] = ['class' => $class, 'revision' => $domainEvent->revision];
        }

        ksort($map);
        $container->setParameter(self::PARAMETER, $map);
    }
}
