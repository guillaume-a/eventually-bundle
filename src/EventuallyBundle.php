<?php

namespace Codr\Eventually\Bundle;

use Codr\Eventually\Application\Attribute\ListensTo;
use Codr\Eventually\Application\Bus\CommandHandlerInterface;
use Codr\Eventually\Application\Bus\QueryHandlerInterface;
use Codr\Eventually\Application\ReadModel\ProjectorInterface;
use Codr\Eventually\Bundle\DependencyInjection\EventRegistryPass;
use Codr\Eventually\Bundle\DependencyInjection\ListensToPass;
use Codr\Eventually\Domain\EventInterface;
use Codr\Eventually\Domain\Upcaster\UpcasterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class EventuallyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(EventInterface::class)
            ->setAutowired(false)
            ->addTag(EventInterface::class);
        $container->registerForAutoconfiguration(UpcasterInterface::class)->addTag(UpcasterInterface::class);
        $container->registerForAutoconfiguration(ProjectorInterface::class)->addTag(ProjectorInterface::class);
        $container->registerForAutoconfiguration(CommandHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => 'command.bus']);
        $container->registerForAutoconfiguration(QueryHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => 'query.bus']);
        $container->registerAttributeForAutoconfiguration(ListensTo::class, new ListensToPass());
        $container->addCompilerPass(new EventRegistryPass());
    }
}
