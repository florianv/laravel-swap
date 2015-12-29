# Laravel Swap

[![Build status][travis-image]][travis-url]
[![Version][version-image]][version-url]
[![Downloads][downloads-image]][downloads-url]

> Integrates [Swap](https://github.com/florianv/swap) with Laravel

## Installation

Install the package via [Composer](https://getcomposer.org):

```bash
$ composer require florianv/laravel-swap
```

## Configuration

Register the service provider and the facade in your configuration:

```php
// config/app.php
'providers' => [
    Florianv\LaravelSwap\SwapServiceProvider::class
],

'aliases' => [
    'Swap' => Florianv\LaravelSwap\Facades\Swap::class
]
```

Publish Swap's configuration:

```bash
$ php artisan vendor:publish
```

By default, `Swap` is configured to use the `FileGetContentsHttpAdapter`, the `YahooFinanceProvider` provider and don't use a cache.

For more informations about all possibilities including Laravel Cache integration, read the comments in the
[configuration file](https://github.com/florianv/laravel-swap/blob/master/config/swap.php).

## Usage

### Via the Facade

```php
Route::get('/', function () {
    $rate = Swap::quote('EUR/USD');
});
```

### Via Injection

```php
use Swap\SwapInterface;

Route::get('/', function (SwapInterface $swap) {
    $rate = $swap->quote('EUR/USD');
});
```

## License

[MIT](https://github.com/florianv/laravel-swap/blob/master/LICENSE)

[travis-url]: https://travis-ci.org/florianv/laravel-swap
[travis-image]: http://img.shields.io/travis/florianv/laravel-swap.svg

[version-url]: https://packagist.org/packages/florianv/laravel-swap
[version-image]: http://img.shields.io/packagist/v/florianv/laravel-swap.svg

[downloads-url]: https://packagist.org/packages/florianv/laravel-swap
[downloads-image]: https://img.shields.io/packagist/dt/florianv/laravel-swap.svg
