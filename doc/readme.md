# Documentation

## 💡 What is Laravel Swap?

- Laravel Swap is the Laravel application of [Swap](https://github.com/florianv/swap), the PHP currency conversion library.
- It registers a service provider (`Swap\Laravel\SwapServiceProvider`) and a `Swap` facade.
- Auto-discovery wires both in Laravel 5.5+ with no manual configuration.
- Configuration publishes to `config/swap.php`.
- Rates are cached through any Laravel cache store you already have.
- Lumen is supported with a few extra lines in `bootstrap/app.php`.

For the wider ecosystem (Swap, Exchanger, Symfony Swap), see the [README](../README.md).

## 🎯 When should you use Laravel Swap?

- Use Laravel Swap when you need exchange rates inside a Laravel or Lumen application: localized prices, invoice totals, multi-currency reporting, historical FX data.
- You do not need to install [Swap](https://github.com/florianv/swap) separately. It is pulled in as a dependency, and Laravel Swap exposes it through Laravel's service container, facade, and cache store.

## 🧠 Why Laravel Swap and not raw Swap?

- **Drop-in.** Auto-discovery in Laravel 5.5+. No manual provider or alias registration.
- **Laravel cache.** Rates are cached through the cache store you already configured (`file`, `redis`, `database`, etc.), no PSR-16 wiring required.
- **Facade.** `Swap::latest('EUR/USD')` from anywhere in the app.
- **Config.** `config/swap.php` exposes providers, options, the cache store, the HTTP client, and the request factory.
- **Lumen.** Supported with the same configuration shape.

## Index

* [Installation](#-installation)
* [Setup](#-setup)
  * [Laravel](#laravel)
  * [Lumen](#lumen)
* [Configuration](#-configuration)
  * [Publishing the config](#publishing-the-config)
  * [Provider configuration](#provider-configuration)
  * [Selecting the HTTP client](#selecting-the-http-client)
* [Usage](#-usage)
  * [The facade](#the-facade)
  * [Latest and historical rates](#latest-and-historical-rates)
  * [Inspecting the rate](#inspecting-the-rate)
* [Caching](#-caching)
  * [Using the Laravel cache](#using-the-laravel-cache)
  * [Per-query options](#per-query-options)
* [Creating a custom service](#-creating-a-custom-service)
  * [Standard service](#standard-service)
  * [Historical service](#historical-service)
* [FAQ](#-faq)

## 📦 Installation

Laravel Swap requires PHP 8.2 or newer.

```bash
composer require florianv/laravel-swap symfony/http-client nyholm/psr7
```

`symfony/http-client` is the PSR-18 HTTP client and `nyholm/psr7` provides the PSR-17 factories. Any PSR-18 / PSR-17 implementation works; for example, if your app already uses Guzzle:

```bash
composer require florianv/laravel-swap php-http/guzzle7-adapter nyholm/psr7
```

## ⚙ Setup

### Laravel

Auto-discovery in Laravel 5.5+ wires the service provider and the `Swap` facade automatically. Nothing more to do for setup.

If auto-discovery is disabled, register them manually in `config/app.php`:

```php
// config/app.php
'providers' => [
    Swap\Laravel\SwapServiceProvider::class,
],

'aliases' => [
    'Swap' => Swap\Laravel\Facades\Swap::class,
],
```

**Laravel 5.7 or older.** If you use the cache, also install the PSR-6 / PSR-16 bridges:

```bash
composer require cache/illuminate-adapter cache/simple-cache-bridge
```

These dependencies are not required on Laravel 5.8+, which [implements PSR-16 natively](https://github.com/laravel/framework/pull/27217).

### Lumen

In `bootstrap/app.php`:

```php
// bootstrap/app.php

// Register the facade
$app->withFacades(true, [
    Swap\Laravel\Facades\Swap::class => 'Swap',
]);

// Load the configuration
$app->configure('swap');

// Register the service provider
$app->register(Swap\Laravel\SwapServiceProvider::class);
```

Copy [the package config](../config/swap.php) to `config/swap.php` to override defaults.

## ⚙ Configuration

### Publishing the config

```bash
php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
```

This creates `config/swap.php` in your application. The default config wires the European Central Bank (free, no API key) so the package works out of the box.

### Provider configuration

Public providers (central banks, national banks, `cryptonator`, `exchangeratehost`, `webservicex`) need no configuration. Use `true` as the value:

```php
// config/swap.php
'services' => [
    'european_central_bank'   => true,
    'national_bank_of_romania' => true,
],
```

Commercial providers require an API key. The option name varies by provider:

| Identifier                       | Required option | Optional flags        |
| -------------------------------- | --------------- | --------------------- |
| `abstract_api`                   | `api_key`       |                       |
| `apilayer_currency_data`         | `api_key`       |                       |
| `apilayer_exchange_rates_data`   | `api_key`       |                       |
| `apilayer_fixer`                 | `api_key`       |                       |
| `coin_layer`                     | `access_key`    | `paid` (bool)         |
| `currency_converter`             | `access_key`    | `enterprise` (bool)   |
| `currency_data_feed`             | `api_key`       |                       |
| `currency_layer`                 | `access_key`    | `enterprise` (bool)   |
| `exchange_rates_api`             | `access_key`    |                       |
| `fastforex`                      | `api_key`       |                       |
| `fixer`                          | `access_key`    |                       |
| `fixer_apilayer`                 | `api_key`       |                       |
| `forge`                          | `api_key`       |                       |
| `open_exchange_rates`            | `app_id`        | `enterprise` (bool)   |
| `xchangeapi`                     | `api-key`       | (note the hyphen)     |
| `xignite`                        | `token`         |                       |

Example:

```php
// config/swap.php
'services' => [
    'apilayer_fixer'      => ['api_key' => env('SWAP_FIXER_KEY')],
    'open_exchange_rates' => ['app_id'  => env('SWAP_OER_APP_ID'), 'enterprise' => false],
    'european_central_bank' => true, // free fallback
],
```

Providers are tried in order. See [How the fallback chain works](https://github.com/florianv/swap#-configuring-multiple-providers-fallback-chain) in the Swap README for the full semantics.

The full provider list with capabilities (base currency, quote currency, historical support) is in the [Swap README's Providers table](https://github.com/florianv/swap#-providers).

### Selecting the HTTP client

By default, Swap auto-discovers a PSR-18 client via `php-http/discovery`. To pass a specific client from the Laravel container, set the `http_client` config key to a service name registered in your container:

```php
// config/swap.php
'http_client' => 'my_http_client',     // a service ID in the Laravel container
'request_factory' => 'my_psr17_factory', // optional PSR-17 factory service ID
```

## ⚡ Usage

### The facade

`Swap::` resolves to `Swap\Swap` from the container. The facade exposes the same two methods:

```php
public static function latest(string $currencyPair, array $options = []): \Exchanger\Contract\ExchangeRate;
public static function historical(string $currencyPair, \DateTimeInterface $date, array $options = []): \Exchanger\Contract\ExchangeRate;
```

You can also resolve it manually:

```php
$swap = app('swap');                       // \Swap\Swap
$swap = app(\Swap\Swap::class);            // same instance
```

### Latest and historical rates

```php
use Swap;

$rate = Swap::latest('EUR/USD');

echo $rate->getValue();                 // e.g. 1.0823
echo $rate->getDate()->format('Y-m-d'); // e.g. 2026-04-29

$rate = Swap::historical('EUR/USD', \Carbon\Carbon::now()->subDays(15));
```

> Currencies are expressed as their [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) code.

### Inspecting the rate

The returned `Exchanger\Contract\ExchangeRate` exposes:

```php
$rate->getValue();         // float
$rate->getDate();          // DateTimeInterface
$rate->getCurrencyPair();  // Exchanger\CurrencyPair
$rate->getProviderName();  // string, the identifier that returned the rate
```

`getProviderName()` is useful when several providers are configured: the returned value is the identifier of the provider that actually answered, for example `european_central_bank`.

## 💾 Caching

### Using the Laravel cache

Set `cache` in `config/swap.php` to any Laravel cache store name:

```php
// config/swap.php
'options' => [
    'cache_ttl'        => 3600,
    'cache_key_prefix' => 'myapp-',
],
'cache' => 'redis', // any Laravel cache store: file, redis, database, ...
```

On Laravel 5.8+ the store is used directly. On Laravel 5.7 or older, the package uses the PSR-6 / PSR-16 bridges installed during [Setup](#-setup).

### Per-query options

Cache behavior can be overridden per call by passing an options array to `latest()` or `historical()`.

| Option             | Type   | Default | Effect                                                                                                |
| ------------------ | ------ | ------- | ----------------------------------------------------------------------------------------------------- |
| `cache_ttl`        | int    | `null`  | Cache TTL in seconds. `null` means entries do not expire.                                             |
| `cache`            | bool   | `true`  | Set to `false` to bypass the cache for this call.                                                     |
| `cache_key_prefix` | string | `""`    | Prefix for the cache key. Max 24 characters (PSR-6 limits keys to 64 chars; the internal hash takes 40). |

PSR-6 does not allow the characters `{}()/\@:` in keys; Swap replaces them with `-`.

```php
Swap::latest('EUR/USD', ['cache' => false]);
Swap::latest('EUR/USD', ['cache_ttl' => 60]);
Swap::latest('EUR/USD', ['cache_key_prefix' => 'currencies-special-']);
```

## 🧩 Creating a custom service

You can register your own provider by implementing the same contract used internally. If your service makes HTTP requests, extend `Exchanger\Service\HttpService`; otherwise extend `Exchanger\Service\Service`.

### Standard service

Create the service class:

```php
namespace App\Swap;

use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\ExchangeRate;
use Exchanger\Service\HttpService;

class ConstantService extends HttpService
{
    public function getExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        // To call an HTTP endpoint:
        // $content = $this->request('https://example.com');

        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }

    public function processOptions(array &$options): void
    {
        if (!isset($options['value'])) {
            throw new \InvalidArgumentException('The "value" option must be provided.');
        }
    }

    public function supportQuery(ExchangeRateQuery $exchangeQuery): bool
    {
        return 'EUR' === $exchangeQuery->getCurrencyPair()->getBaseCurrency();
    }

    public function getName(): string
    {
        return 'constant';
    }
}
```

Register it in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php
use Swap\Service\Registry;
use App\Swap\ConstantService;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Registry::register('constant', ConstantService::class);
    }
}
```

Then use the identifier in `config/swap.php`:

```php
// config/swap.php
'services' => [
    'constant' => ['value' => 10],
],
```

### Historical service

To support historical rates, use the `SupportsHistoricalQueries` trait. Rename `getExchangeRate` to `getLatestExchangeRate` (now `protected`) and implement `getHistoricalExchangeRate`:

```php
use Exchanger\Contract\ExchangeRateQuery;
use Exchanger\Contract\ExchangeRate;
use Exchanger\HistoricalExchangeRateQuery;
use Exchanger\Service\HttpService;
use Exchanger\Service\SupportsHistoricalQueries;

class ConstantService extends HttpService
{
    use SupportsHistoricalQueries;

    protected function getLatestExchangeRate(ExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }

    protected function getHistoricalExchangeRate(HistoricalExchangeRateQuery $exchangeQuery): ExchangeRate
    {
        return $this->createInstantRate($exchangeQuery->getCurrencyPair(), $this->options['value']);
    }
}
```

## ❓ FAQ

#### What happens when every provider fails?

Swap throws an `Exchanger\Exception\ChainException`. Calling `$exception->getExceptions()` on it returns the list of exceptions collected from each provider in the chain.

#### Can I use Laravel Swap without an API key?

Yes. The published config defaults to the European Central Bank, which is free. The national banks, `cryptonator`, `exchangeratehost`, and `webservicex` also work without a key. See the [Swap README's Providers table](https://github.com/florianv/swap#-providers) for the full list.

#### How does Laravel Swap relate to Swap?

Laravel Swap is the Laravel application of Swap. It pulls Swap in as a dependency and exposes it through Laravel's service container, facade, and cache store. If you are not on Laravel or Lumen, use [Swap](https://github.com/florianv/swap) directly.

#### How do I cache rates?

Set `cache` in `config/swap.php` to any Laravel cache store name (for example `redis`, `file`, `database`). See [Caching](#-caching).

#### How do I disable cache for a single query?

Pass `['cache' => false]` as the options argument: `Swap::latest('EUR/USD', ['cache' => false])`.

#### How do I add my own provider?

Implement `Exchanger\Contract\ExchangeRateService` (or extend `HttpService` / `Service`), register it from your `AppServiceProvider` with `Swap\Service\Registry::register()`, then reference its identifier in `config/swap.php`. See [Creating a custom service](#-creating-a-custom-service).

#### Where is the full provider list with capabilities?

In the [Swap README's Providers table](https://github.com/florianv/swap#-providers). It lists every supported identifier with its base currency, quote currency, and historical support.
