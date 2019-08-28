<?php
namespace Kna\YandexCheckoutBundle\Controller;


use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use Kna\YandexCheckoutBundle\Events;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use YandexCheckout\Model\Notification\AbstractNotification;
use YandexCheckout\Model\Notification\NotificationSucceeded;
use YandexCheckout\Model\Notification\NotificationWaitingForCapture;
use YandexCheckout\Model\NotificationEventType;
use YandexCheckout\Model\NotificationType;

class YandexCheckoutController extends Controller
{
    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }

    public function notification(string $key, Request $request): Response
    {
        try {
            if ($key !== $this->getParameter('kna_yandex_checkout.notification_key')) {
                throw new \Exception('Wrong notification key.');
            }

            if ($request->getContentType() !== 'json' || empty($request->getContent())) {
                throw new \Exception('Wrong content type or empty request');
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
                }

                if ($notification instanceof AbstractNotification) {
                    $event = new NotificationEvent();
                    $event->setNotification($notification);

                    $this->getEventDispatcher()->dispatch(Events::NOTIFICATION_RECEIVED, $event);

                    if (!$event->isAccepted()) {
                        throw new \Exception('Notification has not been confirmed');
                    }

                    return $this->json(null);
                }
            }

            throw new \Exception('Wrong request data.');

        } catch (\Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

    }
}