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
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;
use Swap\Builder;
use Swap\Swap;

/**
 * Provides the Swap service.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
final class SwapServiceProvider extends ServiceProvider implements DeferrableProvider
{
    private const CONFIG_SOURCE = __DIR__.'/../config/swap.php';

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

        $this->app->singleton('swap', function (Application $app) {
            $builder = new Builder($app->config->get('swap.options', []));

            if (null !== $cache = $this->getSimpleCache()) {
                $builder->useSimpleCache($cache);
            }

            if (null !== $httpClient = $this->getHttpClient()) {
                $builder->useHttpClient($httpClient);
            }

            if (null !== $requestFactory = $this->getRequestFactory()) {
                $builder->useRequestFactory($requestFactory);
            }

            foreach ($app->config->get('swap.services', []) as $name => $config) {
                if (false === $config) {
                    continue;
                }

                $builder->add($name, is_array($config) ? $config : []);
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
     * Gets the Laravel cache store configured in "swap.cache", if any.
     *
     * Laravel cache repositories implement PSR-16, so the store is passed to Swap as is.
     */
    private function getSimpleCache(): ?CacheInterface
    {
        if ($cache = $this->app->config->get('swap.cache')) {
            return $this->app['cache']->store($cache);
        }

        return null;
    }

    /**
     * Gets the http client.
     *
     * @return \Psr\Http\Client\ClientInterface|null
     */
    private function getHttpClient()
    {
        if ($httpClient = $this->app->config->get('swap.http_client')) {
            return $this->app[$httpClient];
        }

        return null;
    }

    /**
     * Gets the request factory.
     *
     * @return \Psr\Http\Message\RequestFactoryInterface|null
     */
    private function getRequestFactory()
    {
        if ($requestFactory = $this->app->config->get('swap.request_factory')) {
            return $this->app[$requestFactory];
        }

        return null;
    }
}
