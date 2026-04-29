---
title: "Laravel Swap: drop-in currency conversion for Laravel and Lumen"
description: Drop-in Laravel currency conversion. Auto-discovered service provider, facade, and config. Multi-provider exchange rates with fallback and caching. Maintained since 2014.
---

**Drop-in Laravel currency conversion. Install, publish the config, and call `Swap::latest('EUR/USD')` from anywhere. No service container wiring, no boilerplate.**

Wiring an exchange rate library into Laravel usually means service container plumbing, cache bridging, and HTTP client management. Laravel Swap does this for you.

> Used in production Laravel applications since 2014.

Laravel Swap is a drop-in package for **Laravel currency conversion**. Install it, get a facade, and start fetching **Laravel exchange rates** from multiple providers in one call. The service provider is auto-discovered in Laravel 5.5+; configuration publishes to `config/swap.php`; rates are cached through the Laravel cache store you already have. Lumen is supported.

## What is Laravel Swap?

- Laravel Swap is the Laravel application of [Swap](https://github.com/florianv/swap), the PHP currency conversion library.
- It registers a service provider (`Swap\Laravel\SwapServiceProvider`) and a `Swap` facade.
- Auto-discovery wires both in Laravel 5.5+ with no manual configuration.
- Configuration publishes to `config/swap.php`.
- Rates are cached through any Laravel cache store you already have.
- Lumen is supported with a few extra lines in `bootstrap/app.php`.

## When should you use Laravel Swap?

- Use Laravel Swap when you need exchange rates inside a Laravel or Lumen application: localized prices, invoice totals, multi-currency reporting, historical FX data.
- You do not need to install [Swap](https://github.com/florianv/swap) separately. It is pulled in as a dependency, and Laravel Swap exposes it through Laravel's service container, facade, and cache store.

## Why Laravel Swap and not raw Swap?

Using [Swap](https://github.com/florianv/swap) directly inside a Laravel app means three pieces of plumbing on every project: registering it in the service container, bridging your Laravel cache store to the PSR-16 contract Swap expects, and managing the HTTP client and PSR factories yourself. Doable, but boilerplate every project pays for.

Laravel Swap does this for you:

- **Drop-in.** Auto-discovery in Laravel 5.5+. No manual provider or alias registration.
- **Laravel cache.** Rates are cached through the cache store you already configured (`file`, `redis`, `database`, etc.), no PSR-16 wiring required.
- **Facade.** `Swap::latest('EUR/USD')` from anywhere in the app.
- **Config.** `config/swap.php` exposes providers, options, the cache store, the HTTP client, and the request factory.
- **Lumen.** Supported with the same configuration shape.

## Quickstart

Laravel Swap requires PHP 8.2 or newer.

Install via Composer:

```bash
composer require florianv/laravel-swap symfony/http-client nyholm/psr7
```

Auto-discovery wires the service provider and the `Swap` facade in Laravel 5.5+. Publish the config:

```bash
php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
```

The published `config/swap.php` defaults to the European Central Bank (free, no API key). Then call the facade anywhere in the app:

```php
use Swap;

// EUR → USD exchange rate
$rate = Swap::latest('EUR/USD');

$rate->getValue();                 // e.g. 1.0823 (a float)
$rate->getDate()->format('Y-m-d'); // e.g. 2026-04-29
$rate->getProviderName();          // 'european_central_bank' by default

// Convert an amount using the returned rate
$amountInEUR = 100.00;
$amountInUSD = $amountInEUR * $rate->getValue();

// Historical rate
$rate = Swap::historical('EUR/USD', \Carbon\Carbon::now()->subDays(15));
```

Add commercial providers in `config/swap.php` and chain them with the European Central Bank as a free fallback.

## View on GitHub

Source code, full documentation, providers list, and issue tracker:

**[→ View on GitHub](https://github.com/florianv/laravel-swap)**

## Related packages

- [Swap](https://github.com/florianv/swap): easy-to-use PHP currency conversion library.
- [Exchanger](https://github.com/florianv/exchanger): exchange rate provider layer.
- [Laravel Swap](https://github.com/florianv/laravel-swap): Laravel application of Swap (this package).
- [Symfony Swap](https://github.com/florianv/symfony-swap): Symfony integration of Swap.

## Documentation

The full documentation, with the per-provider configuration reference, Lumen setup, custom service registration, and FAQ, is in [doc/readme.md](https://github.com/florianv/laravel-swap/blob/master/doc/readme.md) on the GitHub repository.

---

_Laravel Swap is open to selected partnerships with exchange rate providers._
