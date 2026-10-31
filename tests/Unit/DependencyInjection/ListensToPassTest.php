<?php

namespace Codr\Eventually\Bundle\Tests\Unit\DependencyInjection;

use Codr\Eventually\Application\Attribute\ListensTo;
use Codr\Eventually\Bundle\DependencyInjection\ListensToPass;
use Codr\Eventually\Bundle\Tests\Fixture\CounterWasIncremented;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\DependencyInjection\ChildDefinition;

final class ListensToPassTest extends TestCase
{
    public function testItTagsTheListenerWithItsEventMethodAndPriority(): void
    {
        $definition = new ChildDefinition('');

        (new ListensToPass())($definition, new ListensTo(CounterWasIncremented::class, 'handle', 10), $this->reflect(ListenerStub::class));

        $this->assertSame(
            [['event' => CounterWasIncremented::class, 'method' => 'handle', 'priority' => 10]],
            $definition->getTag('kernel.event_listener'),
        );
    }

    public function testItDefaultsToPriorityZero(): void
    {
        $definition = new ChildDefinition('');

        (new ListensToPass())($definition, new ListensTo(CounterWasIncremented::class, 'handle'), $this->reflect(ListenerStub::class));

        $this->assertSame(
            [['event' => CounterWasIncremented::class, 'method' => 'handle', 'priority' => 0]],
            $definition->getTag('kernel.event_listener'),
        );
    }

    public function testItFailsWhenTheMethodIsMissing(): void
    {
        $this->expectException(LogicException::class);

        (new ListensToPass())(new ChildDefinition(''), new ListensTo(CounterWasIncremented::class, 'missing'), $this->reflect(stdClass::class));
    }

    public function testItFailsWhenTheMethodIsNotPublic(): void
    {
        $this->expectException(LogicException::class);

        (new ListensToPass())(new ChildDefinition(''), new ListensTo(CounterWasIncremented::class, 'hidden'), $this->reflect(ListenerStub::class));
    }

    /**
     * @param class-string $class
     *
     * @return ReflectionClass<object>
     */
    private function reflect(string $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }
}

final class ListenerStub
{
    public function handle(): void
    {
    }

    private function hidden(): void // @phpstan-ignore method.unused
    {
    }
}
