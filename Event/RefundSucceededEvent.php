<?php
namespace Kna\YandexCheckoutBundle\Event;


use Payum\Core\Model\PaymentInterface;
use Symfony\Contracts\EventDispatcher\Event;
use YandexCheckout\Model\RefundInterface as YandexRefundInterface;

class RefundSucceededEvent extends Event
{
    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @var YandexRefundInterface
     */
    protected $refund;

    public function __construct(PaymentInterface $payment, YandexRefundInterface $refund)
    {
        $this->payment = $payment;
        $this->refund = $refund;
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
     * @return YandexRefundInterface
     */
    public function getYandexRefund(): YandexRefundInterface
    {
        return $this->refund;
    }

    /**
     * @param YandexRefundInterface $refund
     */
    public function setRefund(YandexRefundInterface $refund): void
    {
        $this->refund = $refund;
    }

}