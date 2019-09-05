<?php
namespace Kna\YandexCheckoutBundle\Controller;


use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use YandexCheckout\Model\Notification\NotificationCanceled;
use YandexCheckout\Model\Notification\NotificationRefundSucceeded;
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;
use YandexCheckout\Model\NotificationType;

class YandexCheckoutController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function notification(string $key, Request $request): Response
    {
        try {
            if ($key !== $this->getParameter('kna_yandex_checkout.secret_key')) {
                throw new \Exception('Wrong secret key');
            }

            if ($request->getContentType() !== 'json') {
                throw new \Exception('Wrong content type');
            }

            if (empty($request->getContent())) {
                throw new \Exception('Empty request');
            }

            $request->request->replace(json_decode($request->getContent(), true));

            if ($request->request->get('type') === NotificationType::NOTIFICATION) {
                $notification = null;

                switch ($request->get('event')) {
                    case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                        $notification = new NotificationWaitingForCapture($request->request->all());
                        break;
                    case NotificationEventType::PAYMENT_SUCCEEDED:
                        $notification = new NotificationSucceeded($request->request->all());
                        break;
                    case NotificationEventType::PAYMENT_CANCELED:
                        $notification = new NotificationCanceled($request->request->all());
                        break;
                    case NotificationEventType::REFUND_SUCCEEDED:
                        $notification = new NotificationRefundSucceeded($request->request->all());
                        break;
                    default:
                        throw new \Exception('Unknown notification event');
                }

                $event = new NotificationEvent();
                $event->setNotification($notification);

                $this->eventDispatcher->dispatch($event);

                if (!$event->isAccepted()) {
                    throw new \Exception('Notification has not been accepted');
                }

                return $this->json('OK');
            } else {
                throw new \Exception('Unknown notification type');
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

    }
}