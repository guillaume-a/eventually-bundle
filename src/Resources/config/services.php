<?php

use Codr\Eventually\Application\Aggregate\AggregateRepositoryInterface;
use Codr\Eventually\Application\Aggregate\EventSourcedAggregateRepository;
use Codr\Eventually\Application\Bus\CommandBusInterface;
use Codr\Eventually\Application\Bus\QueryBusInterface;
use Codr\Eventually\Application\Event\DomainEventPublisherInterface;
use Codr\Eventually\Application\Event\RecordedEventReaderInterface;
use Codr\Eventually\Application\Metadata\MetadataProviderInterface;
use Codr\Eventually\Application\ReadModel\ProjectorInterface;
use Codr\Eventually\Bundle\Ai\AiFilesInstaller;
use Codr\Eventually\Bundle\Ai\AiInstallHintListener;
use Codr\Eventually\Bundle\Command\InstallAiCommand;
use Codr\Eventually\Bundle\Command\RebuildReadModelsCommand;
use Codr\Eventually\Bundle\Event\EventDispatcherDomainEventPublisher;
use Codr\Eventually\Bundle\Messenger\ActorIdResolverInterface;
use Codr\Eventually\Bundle\Messenger\CommandMetadataContext;
use Codr\Eventually\Bundle\Messenger\CommandMetadataMiddleware;
use Codr\Eventually\Bundle\Messenger\MessengerCommandBus;
use Codr\Eventually\Bundle\Messenger\MessengerQueryBus;
use Codr\Eventually\Bundle\Messenger\NullActorIdResolver;
use Codr\Eventually\Bundle\Messenger\RetryOnConcurrencyMiddleware;
use Codr\Eventually\Bundle\Serializer\AggregateIdNormalizer;
use Codr\Eventually\Bundle\Serializer\SymfonyEventPayloadSerializer;
use Codr\Eventually\Doctrine\Repository\DoctrineEventStore;
use Codr\Eventually\Doctrine\Repository\DoctrineSnapshotStore;
use Codr\Eventually\Doctrine\Serializer\EventPayloadSerializerInterface;
use Codr\Eventually\Domain\EventStoreInterface;
use Codr\Eventually\Domain\SnapshotStoreInterface;
use Codr\Eventually\Domain\Upcaster\UpcasterInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->private();

    $services->set(NullActorIdResolver::class);
    $services->alias(ActorIdResolverInterface::class, NullActorIdResolver::class);

    $services->set(CommandMetadataContext::class)
        ->args([service(ActorIdResolverInterface::class), service('request_stack')]);
    $services->alias(MetadataProviderInterface::class, CommandMetadataContext::class);

    $services->set(CommandMetadataMiddleware::class)
        ->args([service(CommandMetadataContext::class)]);
    $services->set(RetryOnConcurrencyMiddleware::class)
        ->args([service('doctrine'), param('eventually.command_retry.max_attempts')]);

    $services->set(MessengerCommandBus::class)
        ->args([service('command.bus')]);
    $services->alias(CommandBusInterface::class, MessengerCommandBus::class)->public();
    $services->set(MessengerQueryBus::class)
        ->args([service('query.bus')]);
    $services->alias(QueryBusInterface::class, MessengerQueryBus::class)->public();

    $services->set(EventDispatcherDomainEventPublisher::class)
        ->args([service('event_dispatcher')]);
    $services->alias(DomainEventPublisherInterface::class, EventDispatcherDomainEventPublisher::class);

    $services->set(SymfonyEventPayloadSerializer::class)
        ->args([service('serializer'), service('serializer')]);
    $services->alias(EventPayloadSerializerInterface::class, SymfonyEventPayloadSerializer::class);

    $services->set(DoctrineEventStore::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            param('eventually.event_types'),
            tagged_iterator(UpcasterInterface::class),
            service(EventPayloadSerializerInterface::class),
            service(ClockInterface::class),
            service(MetadataProviderInterface::class),
        ]);
    $services->alias(EventStoreInterface::class, DoctrineEventStore::class);
    $services->alias(RecordedEventReaderInterface::class, DoctrineEventStore::class);

    $services->set(DoctrineSnapshotStore::class)
        ->args([service('doctrine.orm.entity_manager'), service(ClockInterface::class)]);
    $services->alias(SnapshotStoreInterface::class, DoctrineSnapshotStore::class);

    $services->set(EventSourcedAggregateRepository::class)
        ->args([
            service(EventStoreInterface::class),
            service(SnapshotStoreInterface::class),
            service(DomainEventPublisherInterface::class),
            param('eventually.snapshot_frequency'),
        ]);
    $services->alias(AggregateRepositoryInterface::class, EventSourcedAggregateRepository::class);

    $services->set(AggregateIdNormalizer::class)->tag('serializer.normalizer');

    $services->set(AiFilesInstaller::class)
        ->args(['%kernel.project_dir%', '%kernel.project_dir%/vendor']);

    $services->set(InstallAiCommand::class)
        ->args([service(AiFilesInstaller::class)])
        ->tag('console.command');

    $services->set(AiInstallHintListener::class)
        ->args([service(AiFilesInstaller::class), param('kernel.debug')])
        ->tag('kernel.event_listener', ['event' => 'console.terminate']);

    $services->set(RebuildReadModelsCommand::class)
        ->args([
            service(RecordedEventReaderInterface::class),
            service('doctrine.orm.entity_manager'),
            tagged_iterator(ProjectorInterface::class),
        ])
        ->tag('console.command');
};
