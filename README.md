# KnaYandexCheckoutBundle

A Symfony Wrapper for the yandex-money/yandex-checkout-sdk-php library.

## Installation

```
composer require kna/yandex-checkout-bundle
```

## Configuring

```
kna_yandex_checkout:
  shopId: '%env('YANDEX_KASSA_SHOP_ID')%'
  secretKey: '%env('YANDEX_KASSA_SECRET_KEY')%'
```

### Obtaining certificate

```
docker run -it --rm --name certbot \
            -v "/etc/letsencrypt:/etc/letsencrypt" \
            -v "/var/lib/letsencrypt:/var/lib/letsencrypt" \
            -p 80:80 \
            certbot/certbot certonly --standalone --preferred-challenges http

```