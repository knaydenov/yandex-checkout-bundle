<?php
namespace Kna\YandexCheckoutBundle\Event;


use Symfony\Contracts\EventDispatcher\Event;
use YandexCheckout\Model\Notification\AbstractNotification;

class NotificationEvent extends Event
{
    /**
     * @var AbstractNotification
     */
    protected $notification;

    /**
     * @var bool
     */
    protected $accepted = false;
    /**
     * @return AbstractNotification
     */
    public function getNotification(): AbstractNotification
    {
        return $this->notification;
    }

    /**
     * @param AbstractNotification $notification
     */
    public function setNotification(AbstractNotification $notification): void
    {
        $this->notification = $notification;
    }

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * @param bool $accepted
     */
    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }
}