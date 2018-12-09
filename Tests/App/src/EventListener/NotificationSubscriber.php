<?php
namespace Kna\YandexCheckoutBundle\Tests\App\EventListener;


use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use Kna\YandexCheckoutBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{

    /**
     * @var \Memcached
     */
    protected $memcached;

    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::NOTIFICATION_RECEIVED => 'onNotificationReceived'
        ];
    }

    public function onNotificationReceived(NotificationEvent $event)
    {
        $data = $this->memcached->get('data') ?? [];
        $data[] = $event->getNotification()->jsonSerialize();
        $this->memcached->set('data', $data);

        $event->setAccepted(true);
    }
}