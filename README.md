# Laravel Swap

[![Tests](https://github.com/florianv/laravel-swap/actions/workflows/tests.yml/badge.svg)](https://github.com/florianv/laravel-swap/actions/workflows/tests.yml)
[![Psalm](https://github.com/florianv/laravel-swap/actions/workflows/psalm.yml/badge.svg)](https://github.com/florianv/laravel-swap/actions/workflows/psalm.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)
[![Version](http://img.shields.io/packagist/v/florianv/laravel-swap.svg?style=flat-square)](https://packagist.org/packages/florianv/laravel-swap)

> _Drop-in currency conversion for Laravel and Lumen. Auto-discovered service provider, facade, and config. Maintained since 2014._

**Install, publish the config, and call `Swap::latest('EUR/USD')` from anywhere. No service container wiring, no boilerplate, no manual cache plumbing.**

Laravel Swap is a drop-in package for **Laravel currency conversion**. Install it, get a facade, and start fetching **Laravel exchange rates** from multiple providers in one call. The service provider is auto-discovered in Laravel 5.5+; configuration publishes to `config/swap.php`; rates are cached through the Laravel cache store you already have. Lumen is supported. Used in real-world Laravel applications since 2014.

## 💡 What is Laravel Swap?

- Laravel Swap is the Laravel application of [Swap](https://github.com/florianv/swap), the PHP currency conversion library.
- It registers a service provider (`Swap\Laravel\SwapServiceProvider`) and a `Swap` facade.
- Auto-discovery wires both in Laravel 5.5+ with no manual configuration.
- Configuration is published to `config/swap.php`.
- Rates are cached through any Laravel cache store you already have.
- Lumen is supported with a few extra lines in `bootstrap/app.php`.

## 🎯 When should you use Laravel Swap?

- Use Laravel Swap when you need exchange rates inside a Laravel or Lumen application: localized prices, invoice totals, multi-currency reporting, historical FX data.
- You do not need to install [Swap](https://github.com/florianv/swap) separately. It is pulled in as a dependency, and Laravel Swap exposes it through Laravel's service container, facade, and cache store.

## 🧠 Why Laravel Swap and not raw Swap?

Using [Swap](https://github.com/florianv/swap) directly inside a Laravel app means three pieces of plumbing on every project: registering it in the service container, bridging your Laravel cache store to the PSR-16 contract Swap expects, and managing the HTTP client and PSR factories yourself. Doable, but boilerplate every project pays for.

Laravel Swap does this for you:

- **Drop-in.** Auto-discovery in Laravel 5.5+. No manual provider or alias registration.
- **Laravel cache integration.** Rates are cached through the cache store you already configured (`file`, `redis`, `database`, etc.), no PSR-16 wiring required.
- **Facade.** `Swap::latest('EUR/USD')` from anywhere in the app.
- **Config.** `config/swap.php` exposes providers, options, the cache store, the HTTP client, and the request factory.
- **Lumen.** Supported with the same configuration shape.

If you are not on Laravel or Lumen, use [Swap](https://github.com/florianv/swap) directly.

## 📦 Installation

Laravel Swap requires PHP 8.2 or newer.

```bash
composer require florianv/laravel-swap symfony/http-client nyholm/psr7
```

That's it. Auto-discovery wires the service provider and the `Swap` facade in Laravel 5.5+. Skip to [Quickstart](#-quickstart).

---

_Optional: any PSR-18 HTTP client paired with a PSR-17 factory works. If your app already uses Guzzle, swap `symfony/http-client` for `php-http/guzzle7-adapter`. For Lumen or older Laravel versions, see [Setup](doc/readme.md#-setup) in the documentation._

## ⚡ Quickstart

> **The `Swap` facade is available everywhere: controllers, jobs, console commands, queue workers, Blade templates. One static call returns a typed exchange rate.**

Out of the box, the package works without an API key. Publish the config to customize providers and caching:

```bash
php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
```

The published `config/swap.php` defaults to the European Central Bank (free, no key). Then call the facade anywhere in the app:

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

Add commercial providers in `config/swap.php`:

```php
// config/swap.php
'services' => [
    // Add a commercial provider with an API key, for example:
    // 'apilayer_fixer'      => ['api_key' => env('SWAP_FIXER_KEY')],
    // 'open_exchange_rates' => ['app_id'  => env('SWAP_OER_APP_ID')],

    // Free fallback for EUR-base pairs:
    'european_central_bank' => true,
],
```

Providers are tried in order. If a provider does not support the requested currency pair, it is skipped silently. If a provider throws an error, the next provider is tried. If every provider fails, a `ChainException` is thrown with all collected errors.

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

## 🛠 Common use cases

- Display localized prices in multi-currency Laravel storefronts.
- Compute invoice totals across currencies in a Laravel or Lumen API.
- Reconcile multi-currency ledgers using historical rates.
- Power internal FX dashboards with rate history.
- Build currency conversion infrastructure for Laravel-based fintech and ERP applications.

## 🧭 Which package should I use?

The Swap ecosystem is a layered toolkit for currency conversion in PHP:

- **Swap.** The easy-to-use, high-level API for plain PHP.
- **Exchanger.** Lower-level, more granular alternative; direct access to provider implementations.
- **Laravel Swap.** Laravel application of Swap (this package).
- **Symfony Swap.** Symfony integration of Swap.

All four packages are MIT-licensed and require PHP 8.2 or newer.

## 📚 Documentation

The full documentation, with the per-provider configuration reference, Lumen setup, custom service registration, and FAQ, is in [doc/readme.md](doc/readme.md). The full provider list with capabilities is in the [Swap README](https://github.com/florianv/swap#-providers).

## 🧩 Related packages

The Swap ecosystem:

- [**Swap**](https://github.com/florianv/swap): easy-to-use PHP currency conversion library.
- [**Exchanger**](https://github.com/florianv/exchanger): exchange rate provider layer.
- [**Laravel Swap**](https://github.com/florianv/laravel-swap): Laravel application of Swap (this package).
- [**Symfony Swap**](https://github.com/florianv/symfony-swap): Symfony integration of Swap.

## 🤝 Sponsorship

The Swap ecosystem is open to selected sponsorships from exchange rate API providers and financial infrastructure companies.

Sponsorship can include:

- Documentation visibility
- Integration examples
- Ecosystem-level visibility across Swap, Exchanger, Laravel Swap, and Symfony Swap

For inquiries, contact the maintainer via [GitHub](https://github.com/florianv).

## 🙌 Contributing

Issues and pull requests are welcome. Please see the existing [issues](https://github.com/florianv/laravel-swap/issues) before opening a new one.

## 📄 License

The MIT License (MIT). Please see [LICENSE](https://github.com/florianv/laravel-swap/blob/master/LICENSE) for more information.

## 👏 Credits

- [Florian Voutzinos](https://github.com/florianv)
- [All contributors](https://github.com/florianv/laravel-swap/contributors)
