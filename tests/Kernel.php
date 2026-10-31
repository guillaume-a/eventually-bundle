<?php

namespace Codr\Eventually\Bundle\Tests;

use Codr\Eventually\Bundle\EventuallyBundle;
use Codr\Eventually\Bundle\Tests\Unit\DependencyInjection\MissingAttributeEvent;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

use function sys_get_temp_dir;

final class Kernel extends BaseKernel
{
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new EventuallyBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
                'validation' => ['enabled' => true],
            ]);
            $container->loadFromExtension('doctrine', [
                'dbal' => ['url' => '%env(resolve:DATABASE_URL)%', 'server_version' => '18.0'],
                'orm' => [
                    'validate_xml_mapping' => true,
                    'mappings' => [
                        'Fixture' => [
                            'type' => 'xml',
                            'is_bundle' => false,
                            'dir' => __DIR__.'/Fixture/mapping',
                            'prefix' => 'Codr\Eventually\Bundle\Tests\Fixture',
                        ],
                    ],
                ],
            ]);
        });
        $loader->load(__DIR__.'/config/services.php');

        if ('invalid_event' === $this->environment) {
            $loader->load(static function (ContainerBuilder $container): void {
                $container->register(MissingAttributeEvent::class)->setAutoconfigured(true);
            });
        }
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/eventually-bundle/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/eventually-bundle/log';
    }
}
