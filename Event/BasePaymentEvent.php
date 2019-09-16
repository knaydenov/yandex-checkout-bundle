<?php
namespace Kna\YandexCheckoutBundle\Event;


use Payum\Core\Model\PaymentInterface;
use Symfony\Contracts\EventDispatcher\Event;
use YandexCheckout\Model\PaymentInterface as YandexPaymentInterface;

class BasePaymentEvent extends Event
{
    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @var YandexPaymentInterface
     */
    protected $yandexPayment;

    public function __construct(PaymentInterface $payment, YandexPaymentInterface $yandexPayment)
    {
        $this->payment = $payment;
        $this->yandexPayment = $yandexPayment;
    }

    /**
     * @return PaymentInterface
     */
    public function getPayment(): PaymentInterface
    {
        return $this->payment;
    }

    /**
     * @param PaymentInterface $payment
     */
    public function setPayment(PaymentInterface $payment): void
    {
        $this->payment = $payment;
    }

    /**
     * @return YandexPaymentInterface
     */
    public function getYandexPayment(): YandexPaymentInterface
    {
        return $this->yandexPayment;
    }

    /**
     * @param YandexPaymentInterface $yandexPayment
     */
    public function setYandexPayment(YandexPaymentInterface $yandexPayment): void
    {
        $this->yandexPayment = $yandexPayment;
    }
}