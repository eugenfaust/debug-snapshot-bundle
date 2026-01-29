<?php

declare(strict_types=1);

namespace Evgenijfaustov\DebugSnapshotBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('debug_snapshot');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->arrayNode('profiles')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('root_class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('max_depth')
                                ->defaultValue(3)
                                ->min(0)
                            ->end()
                            ->integerNode('max_nodes')
                                ->defaultValue(5000)
                                ->min(1)
                            ->end()
                            ->arrayNode('include')
                                ->useAttributeAsKey('class')
                                ->arrayPrototype()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->defaultValue([])
                            ->end()
                            ->arrayNode('pii_fields')
                                ->useAttributeAsKey('class')
                                ->arrayPrototype()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                    ->requiresAtLeastOneElement()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
