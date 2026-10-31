<?php

use Codr\Eventually\Bundle\Messenger\ActorIdResolverInterface;
use Codr\Eventually\Bundle\Tests\Fixture\StaticActorIdResolver;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autowire()->autoconfigure();

    $services->load('Codr\Eventually\Bundle\Tests\Fixture\\', __DIR__.'/../Fixture/')
        ->exclude(__DIR__.'/../Fixture/{Counter.php,CounterView.php,CountCounters.php,StartCounter.php,IncrementCounter.php,FailingMiddleware.php,mapping}');

    $services->alias(ActorIdResolverInterface::class, StaticActorIdResolver::class);

    $services->set(MockClock::class)->args(['2024-01-01 00:00:00'])->public();
    $services->alias(ClockInterface::class, MockClock::class)->public();
};
