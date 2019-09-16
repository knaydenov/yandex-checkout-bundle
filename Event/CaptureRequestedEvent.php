<?php
namespace Kna\YandexCheckoutBundle\Event;


use Payum\Core\Model\PaymentInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CaptureRequestedEvent extends Event
{
    /**
     * @var boolean
     */
    protected $capture;

    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @var float|null
     */
    protected $amount;

    public function __construct(PaymentInterface $payment, bool $capture = false, ?float $amount = null)
    {
        $this->payment = $payment;
        $this->capture = $capture;
        $this->amount = $amount;
    }

    /**
     * @return bool
     */
    public function shouldCapture(): bool
    {
        return $this->capture;
    }

    /**
     * @param bool $capture
     */
    public function setCapture(bool $capture): void
    {
        $this->capture = $capture;
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
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     */
    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }
}