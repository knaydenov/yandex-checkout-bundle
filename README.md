# KnaYandexCheckoutBundle

A Symfony wrapper for the [yandex-money/yandex-checkout-sdk-php](https://github.com/yandex-money/yandex-checkout-sdk-php) library.

## Installation

```
composer require kna/yandex-checkout-bundle
```

## Configuring

```
kna_yandex_checkout:
  shop_id: '%env('YANDEX_CHECKOUT_SHOP_ID')%'
  secret_key: '%env('YANDEX_CHECKOUT_SECRET_KEY')%'
```

## Usage

Use dependency injection:

```php
public function __constructor(\YandexCheckout\Client $client)
{
    $this->client = $client;
}

```

Create event listener:

```php
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
