<?php
namespace Kna\YandexCheckoutBundle\DependencyInjection;



use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class KnaYandexCheckoutExtension extends Extension implements PrependExtensionInterface
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('kna_yandex_checkout.shop_id', $config['shop_id']);
        $container->setParameter('kna_yandex_checkout.secret_key', $config['secret_key']);

        $container->setParameter('kna_yandex_checkout.validate_ip', $config['validate_ip']);
        $container->setParameter('kna_yandex_checkout.valid_ips', $config['valid_ips']);

        $loader->load('services.yaml');

        // Configure Payum

        if (isset($bundles['PayumBundle']) && $config['payum']['enable']) {
            $container->setParameter('kna_yandex_checkout.payum.payment_class', $config['payum']['payment_class']);
            $container->setParameter('kna_yandex_checkout.payum.payment_id_key', $config['payum']['payment_id_key']);
            $container->setParameter('kna_yandex_checkout.payum.force_payment_id', $config['payum']['force_payment_id']);

            $loader->load('payum.yaml');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function prependPayum(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['PayumBundle']) && $config['payum']['enable']) {
            $payumConfig = [
                'gateways' => [
                    'yandex_checkout' => [
                        'factory' => 'yandex_checkout',
                        'shop_id' => $config['shop_id'],
                        'secret_key' => $config['secret_key'],
                        'payment_id_key' => $config['payum']['payment_id_key'],
                        'force_payment_id' => $config['payum']['force_payment_id']
                    ]
                ]
            ];
            $container->prependExtensionConfig('payum', $payumConfig);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->prependPayum($container);

    }
}