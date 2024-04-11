# Documentation

## Sponsors

<table>
   <tr>
      <td><img src="https://assets.apilayer.com/apis/fixer.png" width="50px"/></td>
      <td><a href="https://fixer.io/">Fixer</a> is a simple and lightweight API for foreign exchange rates that supports up to 170 world currencies.</td>
   </tr>
   <tr>
     <td><img src="https://assets.apilayer.com/apis/currency_data.png" width="50px"/></td>
     <td><a href="https://currencylayer.com">Currency Data</a> provides reliable exchange rates and currency conversions for your business up to 168 world currencies.</td>
   </tr>
   <tr>
     <td><img src="https://assets.apilayer.com/apis/exchangerates_data.png" width="50px"/></td>
     <td><a href="https://exchangeratesapi.io">Exchange Rates Data</a> provides reliable exchange rates and currency conversions for your business with over 15 data sources.</td>
   </tr> 
</table>

## Index

* [Installation](#installation)
* [Setup](#setup)
  * [Laravel](#laravel)
  * [Lumen](#lumen)
* [Configuration](#configuration)
* [Usage](#usage)
  * [Retrieving Rates](#retrieving-rates)
  * [Rate Provider](#rate-provider)
* [Cache](#cache)
  * [Rates Caching](#rates-caching)
  * [Query Cache Options](#cache-options)
* [Creating a Service](#creating-a-service)
  * [Standard Service](#standard-service)
  * [Historical Service](#historical-service)
* [Supported Services](#supported-services)  

## Installation

Swap is decoupled from any library sending HTTP requests (like Guzzle), instead it uses an abstraction called [HTTPlug](http://httplug.io/) 
which provides the http layer used to send requests to exchange rate services. 

Below is an example using Curl:

```bash
$ composer require php-http/curl-client nyholm/psr7 php-http/message florianv/laravel-swap
```

## Setup

### Laravel

If you don't use auto-discovery, add the ServiceProvider to the providers array in config/app.php

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

__Laravel 5.7 or lesser :__

If you use cache, add also PSR-6 adapter and PSR-16 bridge cache dependencies :

```bash
$ composer require cache/illuminate-adapter cache/simple-cache-bridge
```

These dependencies are not required with Laravel 5.8 or greater which [implements PSR-16](https://github.com/laravel/framework/pull/27217).

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

By default Swap uses the [Fixer](https://fixer.io/) service, and will fallback to [Currency Data](https://currencylayer.com) in case of failure.

If you wish to use different services, you can modify the `services` configuration:

```php
// app/config/swap.php
'services' => [
  'apilayer_fixer' => ['api_key' => 'Get your key here: https://fixer.io/'],
  'apilayer_currency_data' => ['api_key' => 'Get your key here: https://currencylayer.com'],
  'apilayer_exchange_rates_data' => ['api_key' => 'Get your key here: https://exchangeratesapi.io'],
]    
```

We recommend to use one of the [services that support our project](#sponsors), providing a free plan up to 100 requests per month.

The complete list of all supported services is available [here](#supported-services).

## Usage

### Retrieving Rates

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

### Rate provider

When using the chain service, it can be useful to know which service provided the rate.

You can use the `getProviderName()` function on a rate that gives you the name of the service that returned it:

```php
$name = $rate->getProviderName();
```

For example, if Fixer returned the rate, it will be identical to `fixer`.

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

### Query Cache Options

You can override `Swap` caching options per request.

#### cache_ttl

Set cache TTL in seconds. Default: `null` - cache entries permanently

```php
// Override the global cache_ttl only for this query
$rate = Swap::latest('EUR/USD', ['cache_ttl' => 60]);
$rate = Swap::historical('EUR/USD', $date, ['cache_ttl' => 60]);
```

#### cache

Disable/Enable caching. Default: `true`

```php
// Disable caching for this query
$rate = Swap::latest('EUR/USD', ['cache' => false]);
$rate = Swap::historical('EUR/USD', $date, ['cache' => false]);
```

#### cache_key_prefix

Set the cache key prefix. Default: empty string

There is a limitation of 64 characters for the key length in PSR-6, because of this, key prefix must not exceed 24 characters, as sha1() hash takes 40 symbols.

PSR-6 do not allows characters `{}()/\@:` in key, these characters are replaced with `-`

```php
// Override cache key prefix for this query
$rate = Swap::latest('EUR/USD', ['cache_key_prefix' => 'currencies-special-']);
$rate = Swap::historical('EUR/USD', $date, ['cache_key_prefix' => 'currencies-special-']);
```

## Creating a Service

You want to add a new service to `Swap` ? Great!

If your service must send http requests to retrieve rates, your class must extend the `HttpService` class, otherwise you can extend the more generic `Service` class.

### Standard service

In the following example, we are creating a `Constant` service that returns a constant rate value.

```php
namespace App\Swap;

use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\ExchangeRate;
use Exchanger\Service\HttpService;

class ConstantService extends HttpService
{
    /**
     * Gets the exchange rate.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return ExchangeRate
     */
    public function getExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        // If you want to make a request you can use
        // $content = $this->request('http://example.com');

        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }

    /**
     * Processes the service options.
     *
     * @param array &$options
     *
     * @return void
     */
    public function processOptions(array &$options): void
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
    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        // For example, our service only supports EUR as base currency
        return 'EUR' === $exchangeQuery->getCurrencyPair()->getBaseCurrency();
    }

    /**
     * Gets the name of the exchange rate service.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'constant';
    }
}
```

You will need to register it in the `boot()` method of your `AppServiceProvider`:

```php
// /app/Providers/AppServiceProvider.php
use Swap\Service\Registry;
use App\Swap\ConstantService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Registry::register('constant', ConstantService::class);
    }
}
```

Then you can use it in the config:

```php
// /config/swap.php

'services' => [
    'constant' => ['value' => 10],
],
```

### Historical service

If your service supports retrieving historical rates, you need to use the `SupportsHistoricalQueries` trait.

You will need to rename the `getExchangeRate` method to `getLatestExchangeRate` and switch its visibility to protected, and implement a new `getHistoricalExchangeRate` method:

```php
use Exchanger\Service\SupportsHistoricalQueries;

class ConstantService extends HttpService
{
    use SupportsHistoricalQueries;
    
    /**
     * Gets the exchange rate.
     *
     * @param ExchangeRateQuery $exchangeQuery
     *
     * @return ExchangeRate
     */
    protected function getLatestExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }

    /**
     * Gets an historical rate.
     *
     * @param HistoricalExchangeRateQuery $exchangeQuery
     *
     * @return ExchangeRate
     */
    protected function getHistoricalExchangeRate(HistoricalExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }
}    
```
    
### Supported Services

Here is the complete list of supported services and their possible configurations:

```php
// app/config/swap.php
'services' => [
    'apilayer_fixer' => ['api_key' => 'Get your key here: https://fixer.io/'],
    'apilayer_currency_data' => ['api_key' => 'Get your key here: https://currencylayer.com'],
    'apilayer_exchange_rates_data' => ['api_key' => 'Get your key here: https://exchangeratesapi.io'],
    'abstract_api' => ['api_key' => 'Get your key here: https://app.abstractapi.com/users/signup'],
    'fixer' => ['access_key' => 'YOUR_KEY'],
    'currency_layer' => ['access_key' => 'secret', 'enterprise' => false],
    'exchange_rates_api' => ['access_key' => 'secret'],
    'coin_layer' => ['access_key' => 'secret', 'paid' => false],
    'european_central_bank' => true,
    'national_bank_of_romania' => true,
    'central_bank_of_republic_turkey' => true,
    'central_bank_of_czech_republic' => true,
    'russian_central_bank' => true,
    'bulgarian_national_bank' => true,
    'webservicex' => true,
    'forge' => ['api_key' => 'secret'],
    'cryptonator' => true,
    'currency_data_feed' => ['api_key' => 'secret'],
    'open_exchange_rates' => ['app_id' => 'secret', 'enterprise' => false],
    'xignite' => ['token' => 'token'],
    'xchangeapi' => ['api-key' => 'api-key'],
    'array' => [
        [
            'EUR/USD' => 1.1,
            'EUR/GBP' => 1.5
        ],
        [
            '2017-01-01' => [
                'EUR/USD' => 1.5
            ],
            '2017-01-03' => [
                'EUR/GBP' => 1.3
            ],
        ]
    ],
]
```            
