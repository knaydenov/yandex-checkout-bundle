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
                ->scalarNode('validate_ip')
                    ->defaultTrue()
                ->end()
                ->arrayNode('valid_ips') # https://kassa.yandex.ru/developers/using-api/webhooks#using
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        '185.71.76.0/27',
                        '185.71.77.0/27',
                        '77.75.153.0/25',
                        '77.75.154.128/25',
                        '2a02:5180:0:1509::/64',
                        '2a02:5180:0:2655::/64',
                        '2a02:5180:0:1533::/64',
                        '2a02:5180:0:2669::/64'
                    ])
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}