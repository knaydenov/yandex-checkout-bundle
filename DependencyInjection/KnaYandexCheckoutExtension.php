<?php
namespace Kna\YandexCheckoutBundle\DependencyInjection;



use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KnaYandexCheckoutExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('kna_yandex_checkout.shop_id', $config['shop_id']);
        $container->setParameter('kna_yandex_checkout.secret_key', $config['secret_key']);
        $container->setParameter('kna_yandex_checkout.notification_key', $config['notification_key']);

        $loader->load('services.yaml');
    }
}