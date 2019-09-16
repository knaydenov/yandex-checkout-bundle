# KnaYandexCheckoutBundle

[![Build Status](https://travis-ci.org/knaydenov/yandex-checkout-bundle.svg?branch=master)](https://travis-ci.org/knaydenov/yandex-checkout-bundle)

A Symfony wrapper for the [yandex-money/yandex-checkout-sdk-php](https://github.com/yandex-money/yandex-checkout-sdk-php) library.

## Installation

```shell script
composer require kna/yandex-checkout-bundle
```

## Configuring

Add config:

```yaml
// config/packages/kna_yandex_checkout.yaml

kna_yandex_checkout:
  shop_id: '%env('YANDEX_CHECKOUT_SHOP_ID')%'
  secret_key: '%env('YANDEX_CHECKOUT_SECRET_KEY')%'
  validate_ip: true
  valid_ips:
  - 192.168.1.0/16
```

Add routing:

```yaml
// config/routes.yaml

// ...

kna_yandex_checkout:
  resource: "@KnaYandexCheckoutBundle/Resources/config/routes.yaml"
  prefix: <prefix>

// ...

```

Set ```https://<domain.tld>/<prefix>/<secret_key>``` as URL for notifications and events in the [store settings](https://kassa.yandex.ru/my/tunes).

## Usage

Use dependency injection:

```php
<?php
// src/EventListener/DefaultController.php

namespace App\Controller;


use YandexCheckout\Client;

public function __constructor(Client $client)
{
    $this->client = $client;
}

```

Create event listener:

```php
<?php
// src/EventListener/YandexCheckoutSubscriber.php

namespace App\EventListener;


use Kna\YandexCheckoutBundle\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class YandexCheckoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            NotificationEvent::class => 'onNotificationReceived'
        ];
    }

    public function onNotificationReceived(NotificationEvent $event)
    {
        $notification = $event->getNotification();
        
        // dispatch notification

        $event->setAccepted(true);
    }
}
```

## Payum support

[payum/payum-bundle](https://github.com/Payum/PayumBundle) and [kna/payum-yandex-checkout](https://github.com/knaydenov/payum-yandex-checkout) 
should be installed.

### Configuring

Add config:

```yaml
// config/packages/kna_yandex_checkout.yaml

kna_yandex_checkout:
  // ...
  payum:
    enable: true
    payment_class: App\Entity\Payment # default
    payment_id_key: payment_id
    force_payment_id: true
```

Create event listener:

```php
<?php
// src/EventListener/YandexCheckoutSubscriber.php

namespace App\EventListener;


use Kna\YandexCheckoutBundle\Event\CaptureRequestedEvent;
use Kna\YandexCheckoutBundle\Event\PaymentCanceledEvent;
use Kna\YandexCheckoutBundle\Event\PaymentCapturedEvent;
use Kna\YandexCheckoutBundle\Event\PaymentSucceededEvent;
use Kna\YandexCheckoutBundle\Event\RefundSucceededEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class YandexCheckoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            CaptureRequestedEvent::class => 'onCaptureRequested',
            PaymentCanceledEvent::class => 'onPaymentCancelled',
            PaymentCapturedEvent::class => 'onPaymentCaptured',
            PaymentSucceededEvent::class => 'onPaymentSucceeded',
            RefundSucceededEvent::class => 'onRefundSucceeded',
        ];
    }

    public function onCaptureRequested(CaptureRequestedEvent $event)
    {
        // ...
    }
    
    public function onPaymentCancelled(PaymentCanceledEvent $event)
    {
        // ...
    }
    
    public function onPaymentCaptured(PaymentCapturedEvent $event)
    {
        // ...
    }
    
    public function onPaymentSucceeded(PaymentSucceededEvent $event)
    {
        // ...
    }
    
    public function onRefundSucceeded(RefundSucceededEvent $event)
    {
        // ...
    }
}
```
