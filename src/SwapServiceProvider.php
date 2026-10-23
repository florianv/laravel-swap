<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Swap\Builder;
use Swap\Swap;

/**
 * Provides the Swap service.
 *
 * Not deferred on purpose: a deferred provider registers on first use, after the application's
 * own providers, so it would override their bindings and test doubles, and the config defaults
 * would be missing until Swap is resolved.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
final class SwapServiceProvider extends ServiceProvider
{
    private const CONFIG_SOURCE = __DIR__ . '/../config/swap.php';

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([self::CONFIG_SOURCE => $this->app->configPath('swap.php')]);
    }

    /**
     * Register the application services.
     */
    #[\Override]
    public function register()
    {
        $this->mergeConfigFrom(self::CONFIG_SOURCE, 'swap');

        $this->app->singleton('swap', static function (Application $app): Swap {
            $config = $app->make('config');

            // Typed for any user config, not only the shipped defaults the Laravel plugin infers.
            /** @var array<string, mixed> $options */
            $options = $config->get('swap.options', []);
            $builder = new Builder($options);

            if (null !== $store = self::serviceId($config->get('swap.cache'), 'cache')) {
                $builder->useSimpleCache($app->make('cache')->store($store));
            }

            if (null !== $httpClient = self::serviceId($config->get('swap.http_client'), 'http_client')) {
                /** @psalm-suppress MixedArgument Builder::useHttpClient() rejects anything but a PSR-18 or HTTPlug client. */
                $builder->useHttpClient($app->make($httpClient));
            }

            if (null !== $requestFactory = self::serviceId($config->get('swap.request_factory'), 'request_factory')) {
                /** @psalm-suppress MixedArgument The RequestFactoryInterface parameter type rejects anything else. */
                $builder->useRequestFactory($app->make($requestFactory));
            }

            /** @var array<array-key, array<string, mixed>|bool> $services */
            $services = $config->get('swap.services', []);

            foreach ($services as $name => $serviceOptions) {
                if (false === $serviceOptions) {
                    continue;
                }

                $builder->add((string) $name, \is_array($serviceOptions) ? $serviceOptions : []);
            }

            return $builder->build();
        });

        $this->app->alias('swap', Swap::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     *
     * @psalm-pure
     */
    #[\Override]
    public function provides()
    {
        return [
            'swap',
            Swap::class,
        ];
    }

    /**
     * Returns the container or cache store name set in a "swap.*" option, or null when it is not set.
     *
     * Laravel cache stores implement PSR-16, so the configured store is passed to Swap as is.
     *
     * @throws \InvalidArgumentException when the option is set to something other than a name
     *
     * @psalm-pure
     */
    private static function serviceId(mixed $value, string $option): ?string
    {
        if (null === $value || false === $value || '' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('The "swap.%s" option must be a name, %s given.', $option, get_debug_type($value)));
        }

        return $value;
    }
}
