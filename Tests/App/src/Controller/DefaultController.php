<?php
namespace Kna\YandexCheckoutBundle\Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use YandexCheckout\Client;
use YandexCheckout\Model\MonetaryAmount;
use YandexCheckout\Request\Payments\CreatePaymentRequest;

class DefaultController extends Controller
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
     * @return Client
     */
    public function getYandexCheckoutClient(): Client
    {
        return $this->get('kna_yandex_checkout.client');
    }

    public function index(): Response
    {
        return $this->json($this->memcached->get('data'));
    }

    public function payment(Request $request): Response
    {
        try {
            $request->request->replace(json_decode($request->getContent(), true));

            $client = $this->getYandexCheckoutClient();

            $createPaymentRequest = CreatePaymentRequest::builder()
                ->setMetadata(['accountId' => $request->get('account_id')])
                ->setPaymentMethodData([
                    'type' => 'bank_card'
                ])
                ->setConfirmation([
                    'type' => 'redirect',
                    'return_url' => $request->get('return_url')
                ])
                ->setAmount(new MonetaryAmount($request->get('value'), $request->get('currency', 'RUB')))
                ->build()
            ;

            $createPaymentResponse = $client->createPayment($createPaymentRequest);

            return $this->json([
                'confirmation_url' => $createPaymentResponse->getConfirmation()->offsetGet('confirmation_url')
            ]);
        } catch (\Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

}