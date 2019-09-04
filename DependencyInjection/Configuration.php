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
                ->scalarNode('shop_id')
                    ->defaultNull()
                    ->isRequired()
                ->end()
                ->scalarNode('secret_key')
                    ->defaultNull()
                    ->isRequired()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}