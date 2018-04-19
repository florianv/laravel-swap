<img src="https://github.com/florianv/swap/blob/master/doc/logo.png" width="200px" align="left"/>

> Currency exchange rates library for Laravel and Lumen

[![Build status](http://img.shields.io/travis/florianv/laravel-swap.svg?style=flat-square)](https://travis-ci.org/florianv/laravel-swap)
[![Total Downloads](https://img.shields.io/packagist/dt/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/florianv/laravel-swap.svg?style=flat-square)](https://scrutinizer-ci.com/g/florianv/laravel-swap)
[![Version](http://img.shields.io/packagist/v/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)

**Swap** allows you to retrieve currency exchange rates from various services such as [Fixer](http://fixer.io) or [Google](https://google.com/) and optionally cache the results.

<br /><br />

## QuickStart

### Installation

```bash
$ composer require florianv/laravel-swap php-http/message php-http/guzzle6-adapter
```

### Laravel

Configure the Service Provider and alias:

```php
// /config/app.php
'providers' => [
    Swap\Laravel\SwapServiceProvider::class
],

'aliases' => [
    'Swap' => Swap\Laravel\Facades\Swap::class
]
```

Publish the Package configuration

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

## Services

Here is the list of the currently implemented services.

| Service | Registry Alias | Base Currency | Quote Currency | Historical |
|----------------------------------------------------------------|---------------|----------------------|----------------|----------------|
| [Fixer](http://fixer.io) | `fixer` | * | * | Yes |
| [European Central Bank](http://www.ecb.europa.eu/home/html/index.en.html) | `european_central_bank` | EUR | * | Yes |
| [Google](http://www.google.com/finance) | `google` | * | * | No |
| [Open Exchange Rates](https://openexchangerates.org) | USD (free), * (paid) | * | Yes |
| [Xignite](https://www.xignite.com) | `xignite` | * | * | Yes |
| [WebserviceX](http://www.webservicex.net/ws/default.aspx) | `webservicex` | * | * | No |
| [National Bank of Romania](http://www.bnr.ro) | `national_bank_of_romania` | RON | * | No |
| [Central Bank of the Republic of Turkey](http://www.tcmb.gov.tr) | `central_bank_of_republic_turkey` | * | TRY | No |
| [Central Bank of the Czech Republic](http://www.cnb.cz) | `central_bank_of_czech_republic` | * | CZK | No |
| [Russian Central Bank](http://http://www.cbr.ru) | `russian_central_bank` | * | RUB | Yes |
| [currencylayer](https://currencylayer.com) | `currency_layer` | USD (free), * (paid) | * | Yes |
| [Cryptonator](https://www.cryptonator.com) | `cryptonator` | * Crypto (Limited standard currencies) | * Crypto (Limited standard currencies)  | No |
| [1Forge](https://1forge.com) | `forge` | * (free but limited or paid) | * (free but limited or paid) | No |
| [CurrencyDataFeed](https://currencydatafeed.com) | `currency_data_feed` | * (free but limited or paid) | * (free but limited or paid) | No |
| Array | `array` | * | * | Yes |

## Credits

- [Florian Voutzinos](https://github.com/florianv)
- [All Contributors](https://github.com/florianv/laravel-swap/contributors)

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/florianv/laravel-swap/blob/master/LICENSE) for more information.
