<?php

namespace Codr\Eventually\Bundle\DependencyInjection;

use Codr\Eventually\Bundle\Messenger\CommandMetadataMiddleware;
use Codr\Eventually\Bundle\Messenger\RetryOnConcurrencyMiddleware;
use Codr\Eventually\Doctrine\Entity\StoredEvent;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use function dirname;

final class EventuallyExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $entityDir = dirname(new ReflectionClass(StoredEvent::class)->getFileName() ?: '');

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => 'command.bus',
                'buses' => [
                    'command.bus' => [
                        'middleware' => [
                            CommandMetadataMiddleware::class,
                            RetryOnConcurrencyMiddleware::class,
                            'validation',
                            'doctrine_transaction',
                        ],
                    ],
                    'query.bus' => [
                        'middleware' => ['validation'],
                    ],
                ],
            ],
            'property_info' => ['with_constructor_extractor' => true],
            'serializer' => [
                'mapping' => [
                    'paths' => [dirname(__DIR__).'/Resources/config/serializer'],
                ],
            ],
        ]);

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => ['uuid' => 'Symfony\Bridge\Doctrine\Types\UuidType'],
            ],
            'orm' => [
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'mappings' => [
                    'EventuallyEntities' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => $entityDir,
                        'prefix' => 'Codr\Eventually\Doctrine\Entity',
                    ],
                    'EventuallyViews' => [
                        'type' => 'xml',
                        'is_bundle' => false,
                        'dir' => dirname($entityDir).'/Resources/mapping',
                        'prefix' => 'Codr\Eventually',
                    ],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{snapshot_frequency: int, command_retry: array{max_attempts: int}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('eventually.snapshot_frequency', $config['snapshot_frequency']);
        $container->setParameter('eventually.command_retry.max_attempts', $config['command_retry']['max_attempts']);

        new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'))->load('services.php');
    }
}
