<?php

namespace Codr\Eventually\Bundle\Tests\Unit\DependencyInjection;

use Codr\Eventually\Bundle\DependencyInjection\EventRegistryPass;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasIncremented;
use Codr\Eventually\Domain\EventInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EventRegistryPassTest extends TestCase
{
    public function testItBuildsTheTypeToClassMap(): void
    {
        $container = new ContainerBuilder();
        $container->register(CounterWasIncremented::class)->addTag(EventInterface::class);

        new EventRegistryPass()->process($container);

        self::assertSame(
            ['counter_was_incremented' => ['class' => CounterWasIncremented::class, 'revision' => 1]],
            $container->getParameter(EventRegistryPass::PARAMETER),
        );
    }

    public function testItFailsNamingBothClassesOnDuplicateType(): void
    {
        $container = new ContainerBuilder();
        $container->register(DuplicateTypeEventA::class)->addTag(EventInterface::class);
        $container->register(DuplicateTypeEventB::class)->addTag(EventInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/test\.duplicate.*DuplicateTypeEventA.*DuplicateTypeEventB/');

        new EventRegistryPass()->process($container);
    }

    public function testItFailsNamingTheClassWhenTheAttributeIsMissing(): void
    {
        $container = new ContainerBuilder();
        $container->register(MissingAttributeEvent::class)->addTag(EventInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/MissingAttributeEvent.*DomainEvent/');

        new EventRegistryPass()->process($container);
    }
}
