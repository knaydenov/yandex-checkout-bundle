<?php


namespace Kna\YandexCheckoutBundle\Tests\Controller;


use Kna\YandexCheckoutBundle\Controller\YandexCheckoutController;
use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use YandexCheckout\Model\NotificationType;

class YandexCheckoutControllerTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|MockObject
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var ContainerInterface|MockObject
     */
    protected $container;

    /**
     * @var ParameterBagInterface|MockObject
     */
    protected $parameterBag;

    /**
     * @var SerializerInterface|MockObject
     */
    protected $serializer;

    /**
     * @var ContainerInterface|MockObject
     */
    protected $controller;


    protected function createParameterBag(): ParameterBagInterface
    {
        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = $this
            ->getMockBuilder(ParameterBagInterface::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass()
        ;
        return $parameterBag;
    }

    protected function createController(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger): YandexCheckoutController
    {
        /** @var YandexCheckoutController $controller */
        $controller = $this
            ->getMockBuilder(YandexCheckoutController::class)
            ->setConstructorArgs([$eventDispatcher, $logger])
            ->getMockForAbstractClass()
        ;

        return $controller;
    }

    protected function createEventDispatcher(): EventDispatcherInterface
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->setMethods(['dispatch'])
            ->getMockForAbstractClass()
        ;

        return $eventDispatcher;
    }

    protected function createLogger(): LoggerInterface
    {
        /** @var LoggerInterface $logger */
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass()
        ;

        return $logger;
    }

    protected function createSerializer(): SerializerInterface
    {
        /** @var SerializerInterface|MockObject $serializer */
        $serializer = $this
            ->getMockBuilder(SerializerInterface::class)
            ->setMethods(['serialize'])
            ->getMockForAbstractClass()
        ;

        $serializer->expects($this->any())->method('serialize')->with($this->anything())->willReturnCallback(function ($data, $format, array $context = []) {
            return $data;
        });

        return $serializer;
    }

    protected function createContainer(): ContainerInterface
    {
        /** @var ContainerInterface|MockObject $container */
        $container = $this
            ->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMockForAbstractClass()
        ;

        $container->expects($this->any())->method('has')->with($this->anything())->willReturn(true);
        $container->expects($this->any())->method('get')->with($this->anything())->willReturnMap([
            ['parameter_bag', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->parameterBag],
            ['serializer', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->serializer],
        ]);

        return $container;
    }

    /**
     * @param array $config
     * @return Request
     */
    public function createRequest(array $config = []): Request
    {

        return  new Request(
            $config['query'] ?? [],
            $config['request'] ?? [],
            $config['attributes'] ?? [],
            $config['cookies'] ?? [],
            $config['files'] ?? [],
            $config['server'] ?? [],
            $config['content'] ?? null
        );
    }


    public function setUp()
    {
        $this->eventDispatcher = $this->createEventDispatcher();
        $this->logger = $this->createLogger();
        $this->parameterBag = $this->createParameterBag();
        $this->serializer = $this->createSerializer();
        $this->container = $this->createContainer();
        $this->controller = $this->createController($this->eventDispatcher, $this->logger);
        $this->controller->setContainer($this->container);
    }

    public function testNotificationWithWrongSecretKeyReturnsErrorResponse(): void
    {
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('right_key');
        $request = $this->createRequest();
        $key = 'wrong_key';
        /** @var Response $response */
        $response = $this->controller->notification($key, $request);

        $this->assertEquals('Wrong secret key', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithWrongContentTypeReturnsErrorResponse(): void
    {
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'text/xml']
        ]);

        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('Wrong content type', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithEmptyContentTypeReturnsErrorResponse(): void
    {
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'content' => ''
        ]);


        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('Empty request', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithWrongTypeReturnsErrorResponse(): void
    {
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $notification = ['type' => 'some_wrong_type'];
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'content' => json_encode($notification)
        ]);


        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('Unknown notification type', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithWrongEventReturnsErrorResponse(): void
    {
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $notification = ['type' => NotificationType::NOTIFICATION, 'event' => 'some_worng_event'];
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'content' => json_encode($notification)
        ]);


        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('Unknown notification event', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithUnacceptedEventReturnsErrorResponse(): void
    {
        $notification = [
            "type" => "notification",
            "event" => "payment.waiting_for_capture",
            "object" => [
                "id" => "22d6d597-000f-5000-9000-145f6df21d6f",
                "status" => "waiting_for_capture",
                "paid" => true,
                "amount" => [
                    "value" => "2.00",
                    "currency" => "RUB"
                ],
                "authorization_details" => [
                    "rrn" => "10000000000",
                    "auth_code" => "000000"
                ],
                "created_at" => "2018-07-10T14:27:54.691Z",
                "description" => "Заказ №72",
                "expires_at" => "2018-07-17T14:28:32.484Z",
                "metadata" => [],
                "payment_method" => [
                    "type" => "bank_card",
                    "id" => "22d6d597-000f-5000-9000-145f6df21d6f",
                    "saved" => false,
                    "card" => [
                        "first6" => "555555",
                        "last4" => "4444",
                        "expiry_month" => "07",
                        "expiry_year" => "2021",
                        "card_type" => "MasterCard",
                        "issuer_country" => "RU",
                        "issuer_name" => "Sberbank"
                    ],
                    "title" => "Bank card *4444"
                ],
                "refundable" => false,
                "requestor" => [
                    "type" => "merchant",
                    "account_id" => "100001"
                ],
                "test" => false
            ]
        ];
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'content' => json_encode($notification)
        ]);


        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('Notification has not been accepted', $response->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testNotificationWithAcceptedEventReturnsOkResponse(): void
    {
        $notification = [
            "type" => "notification",
            "event" => "payment.waiting_for_capture",
            "object" => [
                "id" => "22d6d597-000f-5000-9000-145f6df21d6f",
                "status" => "waiting_for_capture",
                "paid" => true,
                "amount" => [
                    "value" => "2.00",
                    "currency" => "RUB"
                ],
                "authorization_details" => [
                    "rrn" => "10000000000",
                    "auth_code" => "000000"
                ],
                "created_at" => "2018-07-10T14:27:54.691Z",
                "description" => "Заказ №72",
                "expires_at" => "2018-07-17T14:28:32.484Z",
                "metadata" => [],
                "payment_method" => [
                    "type" => "bank_card",
                    "id" => "22d6d597-000f-5000-9000-145f6df21d6f",
                    "saved" => false,
                    "card" => [
                        "first6" => "555555",
                        "last4" => "4444",
                        "expiry_month" => "07",
                        "expiry_year" => "2021",
                        "card_type" => "MasterCard",
                        "issuer_country" => "RU",
                        "issuer_name" => "Sberbank"
                    ],
                    "title" => "Bank card *4444"
                ],
                "refundable" => false,
                "requestor" => [
                    "type" => "merchant",
                    "account_id" => "100001"
                ],
                "test" => false
            ]
        ];
        $this->parameterBag->expects($this->any())->method('get')->with('kna_yandex_checkout.secret_key')->willReturn('some_key');
        $request = $this->createRequest([
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'content' => json_encode($notification)
        ]);

        $this->eventDispatcher->expects($this->any())->method('dispatch')->with($this->anything())->willReturnCallback(function (NotificationEvent$event) {
            $event->setAccepted(true);
        });

        /** @var Response $response */
        $response = $this->controller->notification('some_key', $request);

        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}