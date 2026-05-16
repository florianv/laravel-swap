---
title: "Laravel Swap: drop-in currency conversion for Laravel and Lumen"
description: Drop-in Laravel currency conversion. Auto-discovered service provider, facade, and config. Multi-provider exchange rates with fallback and caching. Maintained since 2014.
---

**Drop-in Laravel currency conversion. Install, publish the config, and call `Swap::latest('EUR/USD')` from anywhere. No service container wiring, no boilerplate.**

Wiring an exchange rate library into Laravel usually means service container plumbing, cache bridging, and HTTP client management. Laravel Swap does this for you.

> Used in production Laravel applications since 2014.

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

Laravel Swap is a drop-in package for **Laravel currency conversion**. Install it, get a facade, and start fetching **Laravel exchange rates** from multiple providers in one call. The service provider is auto-discovered in Laravel 5.5+; configuration publishes to `config/swap.php`; rates are cached through the Laravel cache store you already have. Lumen is supported.

## What is Laravel Swap?

- The Laravel application of [Swap](https://github.com/florianv/swap), the PHP currency conversion library.
- A service provider (`Swap\Laravel\SwapServiceProvider`) and a `Swap` facade, auto-discovered in Laravel 5.5+.
- Configuration published to `config/swap.php`.
- Rates cached through any Laravel cache store you already have.
- Lumen support with a few extra lines in `bootstrap/app.php`.

## Installation

Laravel Swap requires PHP 8.2 or newer.

```bash
composer require florianv/laravel-swap symfony/http-client nyholm/psr7
```

Auto-discovery wires the service provider and the `Swap` facade in Laravel 5.5+.

## Quickstart

The package ships a default config that pre-wires **[fastFOREX](https://www.fastforex.io)** (the project's sponsor) as the primary provider, with the European Central Bank as a free fallback. [Grab a free API key](https://www.fastforex.io) and add it to your `.env`:

```bash
SWAP_FASTFOREX_KEY=your_key_here
```

That's it. Without the env var, fastFOREX is skipped and the chain falls back to the European Central Bank, so the package still works out of the box without any key (EUR-base only).

To customize providers, options, or the cache store, publish the config:

```bash
php artisan vendor:publish --provider="Swap\Laravel\SwapServiceProvider"
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

Providers are tried in declaration order. If a provider does not support the requested pair, it is skipped. If it throws, the next is tried. If every provider fails, a `ChainException` is thrown.

## Providers

Laravel Swap supports the 30 exchange rate providers from the underlying [Swap](https://github.com/florianv/swap) library, from commercial APIs to public central banks. The recommended starting point for new projects is **[fastFOREX](https://www.fastforex.io)** (`fastforex`): a real-time JSON API covering 160+ fiat currencies and 500+ cryptocurrencies, with up to 55 years of history, sourced from trusted feeds including world banks.

The full per-provider configuration reference (option name, optional flags) is in the [documentation](https://github.com/florianv/laravel-swap/blob/master/doc/readme.md#-provider-configuration).

## Ecosystem

- [Swap](https://github.com/florianv/swap): easy-to-use PHP currency conversion library.
- [Exchanger](https://github.com/florianv/exchanger): exchange rate provider layer.
- [Laravel Swap](https://github.com/florianv/laravel-swap): Laravel application of Swap (this package).
- [Symfony Swap](https://github.com/florianv/symfony-swap): Symfony integration of Swap.

## Documentation & source

- **Source code, issues and pull requests**: [github.com/florianv/laravel-swap](https://github.com/florianv/laravel-swap)
- **Full documentation** (Lumen setup, provider configuration, caching, custom services): [doc/readme.md](https://github.com/florianv/laravel-swap/blob/master/doc/readme.md)

---

_Laravel Swap is open to selected partnerships with exchange rate providers and financial infrastructure companies. For inquiries, contact the maintainer via [GitHub](https://github.com/florianv)._
