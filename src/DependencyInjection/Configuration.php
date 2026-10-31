<?php

namespace Codr\Eventually\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('eventually');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('snapshot_frequency')->min(1)->defaultValue(20)->end()
                ->arrayNode('command_retry')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_attempts')->min(1)->defaultValue(3)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
