<?php
namespace Kna\YandexCheckoutBundle\EventListener;


use Kna\YandexCheckoutBundle\Event\PaymentCanceledEvent;
use Kna\YandexCheckoutBundle\Event\PaymentCapturedEvent;
use Kna\YandexCheckoutBundle\Event\CaptureRequestedEvent;
use Kna\YandexCheckoutBundle\Event\PaymentSucceededEvent;
use Kna\YandexCheckoutBundle\Event\RefundSucceededEvent;
use Doctrine\ORM\EntityManagerInterface;
use Kna\Payum\YandexCheckout\Request\Api\GetPaymentInfo;
use Kna\Payum\YandexCheckout\Request\Sync;
use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use Payum\Core\Model\PaymentInterface as PayumPaymentInterface;
use YandexCheckout\Model\PaymentInterface as YandexPaymentInterface;
use Payum\Core\Payum;
use Payum\Core\Request\Cancel;
use Payum\Core\Request\Capture;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use YandexCheckout\Model\Notification\NotificationCanceled;
use YandexCheckout\Model\Notification\NotificationRefundSucceeded;
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;

class PayumSubscriber implements EventSubscriberInterface
{
    /**
     * @var Payum
     */
    protected $payum;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $paymentClass;

    /**
     * @var string
     */
    protected $paymentIdKey;

    public function __construct(
        Payum $payum,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager,
        string $paymentClass,
        string $paymentIdKey
    )
    {
        $this->payum = $payum;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->paymentClass = $paymentClass;
        $this->paymentIdKey = $paymentIdKey;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NotificationEvent::class => 'onNotificationReceived'
        ];
    }

    /**
     * @param NotificationEvent $event
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function onNotificationReceived(NotificationEvent $event)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $notification = $event->getNotification();

            switch (true) {
                case $notification instanceof NotificationWaitingForCapture:
                    $this->onNotificationWaitingForCapture($notification);
                    break;
                case $notification instanceof NotificationSucceeded:
                    $this->onNotificationSucceeded($notification);
                    break;
                case $notification instanceof NotificationCanceled:
                    $this->onNotificationCanceled($notification);
                    break;
                case $notification instanceof NotificationRefundSucceeded:
                    $this->onNotificationRefundSucceeded($notification);
                    break;
                default:
                    throw new \LogicException('Unknown notification');
            }

            $event->setAccepted(true);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }

    }

    /**
     * @param NotificationWaitingForCapture $notification
     */
    protected function onNotificationWaitingForCapture(NotificationWaitingForCapture $notification)
    {
        $yandexPayment = $notification->getObject();

        $payumPayment = $this->resolvePayment($yandexPayment);

        $this->payum->getGateway('yandex_checkout')->execute(new Sync($payumPayment, $yandexPayment));

        $captureRequestedEvent = new CaptureRequestedEvent($payumPayment);
        $this->eventDispatcher->dispatch($captureRequestedEvent);

        if (!$captureRequestedEvent->shouldCapture()) {
            $this->payum->getGateway('yandex_checkout')->execute(new Cancel($payumPayment));
            return;
        }

        $this->payum->getGateway('yandex_checkout')->execute(new Capture($payumPayment));
        $this->eventDispatcher->dispatch(new PaymentCapturedEvent($payumPayment, $yandexPayment));
    }

    /**
     * @param NotificationSucceeded $notification
     */
    protected function onNotificationSucceeded(NotificationSucceeded $notification)
    {
        $yandexPayment = $notification->getObject();

        $payumPayment = $this->resolvePayment($yandexPayment);

        $this->payum->getGateway('yandex_checkout')->execute(new Sync($payumPayment, $yandexPayment));

        $this->eventDispatcher->dispatch(new PaymentSucceededEvent($payumPayment, $yandexPayment));
    }

    /**
     * @param NotificationCanceled $notification
     */
    protected function onNotificationCanceled(NotificationCanceled $notification)
    {
        $yandexPayment = $notification->getObject();

        $payumPayment = $this->resolvePayment($yandexPayment);

        $this->payum->getGateway('yandex_checkout')->execute(new Sync($payumPayment, $notification->getObject()));
        $this->eventDispatcher->dispatch(new PaymentCanceledEvent($payumPayment, $yandexPayment));
    }

    /**
     * @param NotificationRefundSucceeded $notification
     */
    protected function onNotificationRefundSucceeded(NotificationRefundSucceeded $notification)
    {
        $yandexRefund = $notification->getObject();

        $this->payum->getGateway('yandex_checkout')->execute($paymentInfo = new GetPaymentInfo($yandexRefund->getPaymentId()));

        $yandexPayment = $paymentInfo->getPayment();

        $payumPayment = $this->resolvePayment($yandexPayment);

        $this->payum->getGateway('yandex_checkout')->execute(new Sync($payumPayment, $yandexRefund));

        $this->eventDispatcher->dispatch(new RefundSucceededEvent($payumPayment, $yandexRefund));
    }

    /**
     * @param YandexPaymentInterface $yandexPayment
     * @return PayumPaymentInterface
     */
    protected function resolvePayment(YandexPaymentInterface $yandexPayment): ?PayumPaymentInterface
    {
        if (!$yandexPayment->getMetadata()->offsetExists($this->paymentIdKey)) {
            throw new \RuntimeException(sprintf('Key "%s" not found in metadata', $this->paymentIdKey));
        }

        /** @var PayumPaymentInterface $payment */
        $payment = $this->payum->getStorage($this->paymentClass)->find($yandexPayment->getMetadata()->offsetGet($this->paymentIdKey));

        if (!$payment) {
            throw new \RuntimeException('Payment not found');
        }

        return $payment;
    }

}