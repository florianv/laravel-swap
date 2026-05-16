# Laravel Swap

[![Tests](https://github.com/florianv/laravel-swap/actions/workflows/tests.yml/badge.svg)](https://github.com/florianv/laravel-swap/actions/workflows/tests.yml)
[![Psalm](https://github.com/florianv/laravel-swap/actions/workflows/psalm.yml/badge.svg)](https://github.com/florianv/laravel-swap/actions/workflows/psalm.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)
[![Version](http://img.shields.io/packagist/v/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)

> _Drop-in currency conversion for Laravel and Lumen. Auto-discovered service provider, facade, and config. Maintained since 2014._

<table>
   <tr>
      <td width="220" align="center">
         <a href="https://www.fastforex.io" target="_blank" rel="noopener">
            <img src="https://console.fastforex.io/img/fastforex/logo-bk-1k.svg" width="180px" alt="fastFOREX"/>
         </a>
      </td>
      <td>
         <strong>Sponsored by <a href="https://www.fastforex.io" target="_blank" rel="noopener">fastFOREX</a>.</strong> Real-time JSON API, 160+ currencies, 55+ years of history, 500+ cryptocurrencies. <strong>Free tier</strong>; paid plans from $18/month.
         <a href="https://www.fastforex.io" target="_blank" rel="noopener"><strong>→ Get a free fastFOREX API key</strong></a>
      </td>
   </tr>
</table>

**Install, publish the config, and call `Swap::latest('EUR/USD')` from anywhere. No service container wiring, no boilerplate, no manual cache plumbing.**

Laravel Swap is a drop-in package for **Laravel currency conversion**. The service provider is auto-discovered in Laravel 5.5+; configuration publishes to `config/swap.php`; rates are cached through the Laravel cache store you already have. Lumen is supported.

## 💡 What is Laravel Swap?

- The Laravel application of [Swap](https://github.com/florianv/swap), the PHP currency conversion library.
- A service provider (`Swap\Laravel\SwapServiceProvider`) and a `Swap` facade, auto-discovered in Laravel 5.5+.
- Configuration published to `config/swap.php`.
- Rates cached through any Laravel cache store you already have.
- Lumen support with a few extra lines in `bootstrap/app.php`.

## 📦 Installation

Laravel Swap requires PHP 8.2 or newer.

```bash
composer require florianv/laravel-swap symfony/http-client nyholm/psr7
```

Auto-discovery wires the service provider and the `Swap` facade in Laravel 5.5+. For Lumen or older Laravel versions, see [Setup](doc/readme.md#-setup) in the documentation.

## ⚡ Quickstart

The package ships a default `config/swap.php` that uses the European Central Bank, so it works out of the box without an API key. For production, switch to a commercial provider. We recommend **[fastFOREX](https://www.fastforex.io)** (the project's sponsor): a real-time JSON API behind a single `api_key`, [free tier available](https://www.fastforex.io).

```bash
php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
```

In `config/swap.php`:

```php
// config/swap.php
'services' => [
    // Recommended: fastFOREX. Get a free API key at https://www.fastforex.io
    'fastforex' => ['api_key' => env('SWAP_FASTFOREX_KEY')],

    // Free fallback for EUR-base pairs
    'european_central_bank' => true,
],
```

Then call the facade from anywhere in the app:

```php
use Swap;

// EUR → USD exchange rate
$rate = Swap::latest('EUR/USD');

$rate->getValue();                 // e.g. 1.0823 (a float)
$rate->getDate()->format('Y-m-d'); // e.g. 2026-04-29
$rate->getProviderName();          // 'fastforex'

// Convert an amount using the returned rate
$amountInEUR = 100.00;
$amountInUSD = $amountInEUR * $rate->getValue();

// Historical rate
$rate = Swap::historical('EUR/USD', \Carbon\Carbon::now()->subDays(15));
```

Providers are tried in declaration order. If a provider does not support the requested currency pair, it is skipped silently. If a provider throws an error, the next provider is tried. If every provider fails, a `ChainException` is thrown with all collected errors.

## 💾 Caching

Set `cache` in `config/swap.php` to any Laravel cache store name:

```php
// config/swap.php
'options' => [
    'cache_ttl' => 3600,
],
'cache' => 'redis', // any Laravel cache store: file, redis, database, ...
```

Per-query overrides:

```php
Swap::latest('EUR/USD', ['cache' => false]);
Swap::latest('EUR/USD', ['cache_ttl' => 60]);
```

See the [documentation](doc/readme.md#-caching) for the full reference, including cache key prefixes and PSR-6 limitations.

## 📊 Providers

Laravel Swap supports the 30 exchange rate providers from the underlying [Swap](https://github.com/florianv/swap) library. The recommended starting point for new projects is **[fastFOREX](https://www.fastforex.io)** (`fastforex`), the project's sponsor: live rates across 160+ fiat currencies and 500+ cryptocurrencies, with up to 55 years of history.

The full per-provider configuration reference (option name, optional flags, capabilities) is in the [documentation](doc/readme.md#-provider-configuration).

## 🎯 When should you use Laravel Swap?

- Use Laravel Swap when you need exchange rates inside a Laravel or Lumen application: localized prices, invoice totals, multi-currency reporting, historical FX data.
- You do not need to install [Swap](https://github.com/florianv/swap) separately. It is pulled in as a dependency, and Laravel Swap exposes it through Laravel's service container, facade, and cache store.

## 🛠 Common use cases

- Display localized prices in multi-currency Laravel storefronts.
- Compute invoice totals across currencies in a Laravel or Lumen API.
- Reconcile multi-currency ledgers using historical rates.
- Power internal FX dashboards with rate history.
- Build currency conversion infrastructure for Laravel-based fintech and ERP applications.

## 🧭 Which package should I use?

The Swap ecosystem is a layered toolkit for currency conversion in PHP:

- [**Swap**](https://github.com/florianv/swap). The easy-to-use, high-level API for plain PHP.
- [**Exchanger**](https://github.com/florianv/exchanger). Lower-level, more granular alternative; direct access to provider implementations.
- [**Laravel Swap**](https://github.com/florianv/laravel-swap). Laravel application of Swap (this package).
- [**Symfony Swap**](https://github.com/florianv/symfony-swap). Symfony integration of Swap.

All four packages are MIT-licensed and require PHP 8.2 or newer.

## 📚 Documentation

The full documentation, with Lumen setup, per-provider configuration, custom service registration, and FAQ, is in [`doc/readme.md`](doc/readme.md).

## 🙌 Contributing

Issues and pull requests are welcome. Please see the existing [issues](https://github.com/florianv/laravel-swap/issues) before opening a new one.

## 📄 License

The MIT License (MIT). Please see [LICENSE](https://github.com/florianv/laravel-swap/blob/master/LICENSE) for more information.

## 👏 Credits

- [Florian Voutzinos](https://github.com/florianv)
- [All contributors](https://github.com/florianv/laravel-swap/contributors)
