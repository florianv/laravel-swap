# Documentation

## Index
* [Installation](#installation)
* [Setup](#setup)
 * [Laravel](#laravel)
 * [Lumen](#lumen)
* [Configuration](#configuration)
* [Usage](#usage)
* [Cache](#cache)
 * [Rates Caching](#rates-caching)
  * [Cache Options](#cache-options)
* [Service](#service)
  * [Creating a Service](#creating-a-service)
  * [Supported Services](#supported-services)  

## Installation

Swap is decoupled from any library sending HTTP requests (like Guzzle), instead it uses an abstraction called [HTTPlug](http://httplug.io/) 
which provides the http layer used to send requests to exchange rate services. 

Below is an example using [Guzzle 6](http://docs.guzzlephp.org/en/latest/index.html):

```bash
composer require florianv/laravel-swap php-http/message php-http/guzzle6-adapter
```

## Setup

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

## Configuration

By default Swap uses the [Fixer.io](http://fixer.io) service to fetch rates.

If you wish to use different services, you can modify the `services` configuration:

```php
// app/config/swap.php
'services' => [
    'fixer' => true,
    'yahoo' => true,
]    
```

With this configuration, Swap will first use [Fixer.io](http://fixer.io) and fallback to [Yahoo](https://finance.yahoo.com) in case of failure.

> You can consult the list of the supported services and their options [here](#supported-services)

## Usage

In order to get rates, you can use the `latest()` or `historical()` methods on `Swap`:

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

## Cache

### Rates Caching

It is possible to cache rates during a given time using the Laravel cache store of your choice.

```php
// app/config/swap.php
[
    'options' => [
        'cache_ttl' => 3600
    ],
    
    'cache' => 'file'
]
```

Rates are now cached using the Laravel `file` store during 3600 seconds.

### Cache Options

You can override the `cache_ttl` per request:

```php
// Overrides the global cache ttl to 60 seconds
$rate = Swap::latest('EUR/USD', ['cache_ttl' => 60]);
$rate = Swap::historical('EUR/USD', $date, ['cache_ttl' => 60]);

// Disable the cache
$rate = Swap::latest('EUR/USD', ['cache' => false]);
$rate = Swap::historical('EUR/USD', $date, ['cache' => false]);
```

## Service

### Creating a Service

You want to add a new service to `Swap` ? Great!

First you must check if the service supports retrieval of historical rates. If it's the case, you must extend the `HistoricalService` class,
otherwise use the `Service` class.

In the following example, we are creating a `Constant` service that returns a constant rate value.

```php
use Exchanger\Service\Service;
use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\ExchangeRate;
use Swap\Service\Registry;
use Swap\Builder;

class ConstantService extends Service
{
    /**
     * Gets the exchange rate.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return ExchangeRate
     */
    public function getExchangeRate(ExchangeRateQuery $exchangeQuery)
    {
        // If you want to make a request you can use
        $content = $this->request('http://example.com');

        return new ExchangeRate($this->options['value']);
    }

    /**
     * Processes the service options.
     *
     * @param array &$options
     *
     * @return array
     */
    public function processOptions(array &$options)
    {
        if (!isset($options['value'])) {
            throw new \InvalidArgumentException('The "value" option must be provided.');
        }
    }

    /**
     * Tells if the service supports the exchange rate query.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return bool
     */
    public function supportQuery(ExchangeRateQuery $exchangeQuery)
    {
        // For example, our service only supports EUR as base currency
        return 'EUR' === $exchangeQuery->getCurrencyPair()->getBaseCurrency();
    }
}
```

In order to register your service, you need to tag it as `swap.service` in your service provider:

```php
// Create a custom service 'constant_service' that always return 1
$this->app->singleton('constant_service', function ($app) {
    return new ConstantService(null, null, ['value' => 1]);
});

// Tag the service as 'swap.service'
$this->app->tag('constant_service', 'swap.service');
```

> If you want your service to be called first (before the configured Swap ones),
you will need to declare your service provider before the `Swap\Laravel\SwapServiceProvider::class`
in your `config/app.php`
    
### Supported Services

Here is the complete list of supported services and their possible configurations:

```php
// app/config/swap.php
'services' => [
    'central_bank_of_czech_republic' => true,
    'central_bank_of_republic_turkey' => true,
    'currency_layer' => ['access_key' => 'secret', 'enterprise' => false],
    'european_central_bank' => true,
    'fixer' => true,
    'google' => true,
    'national_bank_of_romania' => true,
    'open_exchange_rates' => ['app_id' => 'secret', 'enterprise' => false],
    'array' => [['EUR/USD' => new ExchangeRate('1.5')]],
    'webservicex' => true,
    'xignite' => ['token' => 'token'],
    'yahoo' => true,
    'russian_central_bank' => true
]
```            
