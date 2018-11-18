<?php
namespace Kna\YandexCheckoutBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('kna_yandex_checkout');

        $root
            ->children()
                ->scalarNode('shopId')
                    ->isRequired()
                ->end()
                ->scalarNode('secretKey')
                    ->isRequired()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}