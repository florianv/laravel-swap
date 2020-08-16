# <img src="https://s3.amazonaws.com/swap.assets/swap_logo.png" height="30px" width="30px"/> Laravel Swap

[![Build status](http://img.shields.io/travis/florianv/laravel-swap.svg?style=flat-square)](https://travis-ci.org/florianv/laravel-swap)
[![Total Downloads](https://img.shields.io/packagist/dt/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/florianv/laravel-swap.svg?style=flat-square)](https://scrutinizer-ci.com/g/florianv/laravel-swap)
[![Version](http://img.shields.io/packagist/v/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)

Swap allows you to retrieve currency exchange rates from various services such as **[Fixer](https://fixer.io)** or **[currencylayer](https://currencylayer.com)** and optionally cache the results.

## QuickStart

### Installation

```bash
$ composer require php-http/curl-client nyholm/psr7 php-http/message florianv/laravel-swap
```

### Laravel 5.7 or lesser

If you use cache, add also PSR-6 adapter and PSR-16 bridge cache dependencies :

```bash
$ composer require cache/illuminate-adapter cache/simple-cache-bridge
```

These dependencies are not required with Laravel 5.8 or greater which [implements PSR-16](https://github.com/laravel/framework/pull/27217).

### Laravel 5.5+

If you don't use auto-discovery, add the `ServiceProvider` to the providers array in `config/app.php`:

```php
// /config/app.php
'providers' => [
    Swap\Laravel\SwapServiceProvider::class
],
```

If you want to use the facade to log messages, add this to your facades in app.php:

```
'aliases' => [
    'Swap' => Swap\Laravel\Facades\Swap::class
]
```

Copy the package config to your local config with the publish command:

```bash
$ php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
```

### Lumen

Configure the Service Provider and alias:

```php
// /boostrap/app.php

// Register the facade
$app->withFacades(true, [
    Swap\Laravel\Facades\Swap::class => 'Swap'
]);

// Load the configuration
$app->configure('swap');

// Register the service provider
$app->register(Swap\Laravel\SwapServiceProvider::class);
```

Copy the [configuration](config/swap.php) to `/config/swap.php` if you wish to override it.

## Usage

```php
// Get the latest EUR/USD rate
$rate = Swap::latest('EUR/USD');

// 1.129
$rate->getValue();

// 2016-08-26
$rate->getDate()->format('Y-m-d');

// Get the EUR/USD rate yesterday
$rate = Swap::historical('EUR/USD', Carbon\Carbon::yesterday());
```

## Documentation

The complete documentation can be found [here](https://github.com/florianv/laravel-swap/blob/master/doc/readme.md).

## Sponsors :heart_eyes: 

We are proudly supported by the following echange rate providers offering *free plans up to 1,000 requests per day*:

<img src="https://s3.amazonaws.com/swap.assets/fixer_icon.png?v=2" height="20px" width="20px"/> **[Fixer](https://fixer.io)**

Fixer is a simple and lightweight API for foreign exchange rates that supports up to 170 world currencies.
They provide real-time rates and historical data, however, EUR is the only available base currency on the free plan.

<img src="https://s3.amazonaws.com/swap.assets/currencylayer_icon.png" height="20px" width="20px"/> **[currencylayer](https://currencylayer.com)**

Currencylayer provides reliable exchange rates and currency conversions for your business up to 168 world currencies.
They provide real-time rates and historical data, however, USD is the only available base currency on the free plan.

## Services

Here is the list of the currently implemented services:

| Service | Base Currency | Quote Currency | Historical |
|---------------------------------------------------------------------------|----------------------|----------------|----------------|
| [Fixer](https://fixer.io) | EUR (free, no SSL), * (paid) | * | Yes |
| [currencylayer](https://currencylayer.com) | USD (free), * (paid) | * | Yes |
| [coinlayer](https://coinlayer.com) | * Crypto (Limited standard currencies) | * Crypto (Limited standard currencies) | Yes |
| [European Central Bank](https://www.ecb.europa.eu/home/html/index.en.html) | EUR | * | Yes |
| [National Bank of Romania](http://www.bnr.ro) | RON | * | Yes |
| [Central Bank of the Republic of Turkey](http://www.tcmb.gov.tr) | * | TRY | Yes |
| [Central Bank of the Czech Republic](https://www.cnb.cz) | * | CZK | Yes |
| [Central Bank of Russia](https://cbr.ru) | * | RUB | Yes |
| [Bulgarian National Bank](http://bnb.bg) | * | BGN | Yes |
| [WebserviceX](http://www.webservicex.net) | * | * | No |
| [1Forge](https://1forge.com) | * (free but limited or paid) | * (free but limited or paid) | No |
| [Cryptonator](https://www.cryptonator.com) | * Crypto (Limited standard currencies) | * Crypto (Limited standard currencies)  | No |
| [CurrencyDataFeed](https://currencydatafeed.com) | * (free but limited or paid) | * (free but limited or paid) | No |
| [Open Exchange Rates](https://openexchangerates.org) | USD (free), * (paid) | * | Yes |
| [Xignite](https://www.xignite.com) | * | * | Yes |
| [Currency Converter API](https://www.currencyconverterapi.com) | * | * | Yes (free but limited or paid) |
| [xChangeApi.com](https://xchangeapi.com) | * | * | Yes |
| Array | * | * | Yes |

## Credits

- [Florian Voutzinos](https://github.com/florianv)
- [All Contributors](https://github.com/florianv/laravel-swap/contributors)

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/florianv/laravel-swap/blob/master/LICENSE) for more information.
