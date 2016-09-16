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

use Exchanger\Exchanger;
use Exchanger\Service\Chain;
use Exchanger\Service\PhpArray;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Swap\Swap;

/**
 * Provides the Swap service.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
final class SwapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $source = realpath(__DIR__.'/../config/swap.php');
        $this->publishes([$source => config_path('swap.php')]);
        $this->mergeConfigFrom($source, 'swap');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerHttp($this->app);
        $this->registerCacheItemPool($this->app);
        $this->registerChain($this->app);
        $this->registerExchangeRateProvider($this->app);
        $this->registerSwap($this->app);
    }

    /**
     * Register the http related stuff.
     *
     * @param Application $app
     */
    private function registerHttp(Application $app)
    {
        $app->singleton('swap.http_client', function ($app) {
            if ($httpClient = $app->config->get('swap.http_client')) {
                return $app[$httpClient];
            }

            return HttpClientDiscovery::find();
        });

        $app->singleton('swap.request_factory', function ($app) {
            if ($requestFactory = $app->config->get('swap.request_factory')) {
                return $app[$requestFactory];
            }

            return MessageFactoryDiscovery::find();
        });
    }

    /**
     * Register the core services.
     *
     * @param Application $app
     */
    private function registerServices(Application $app)
    {
        foreach ($app->config->get('swap.services', []) as $name => $config) {
            if (false === $config) {
                continue;
            }

            $camelized = str_replace('_', '', ucwords($name, '_'));
            $class = 'Exchanger\\Service\\' . $camelized;
            $serviceName = sprintf('swap.service.%s', $name);

            // The PhpArray service is a particular case
            if ('array' === $name) {
                return $app->singleton($serviceName, function () use ($config) {
                    return new PhpArray($config);
                });
            }

            // Process the regular services
            if (!class_exists($class)) {
                throw new \RuntimeException(sprintf('The service "%s" does not exist.', $name));
            }

            if (!is_array($config)) {
                $config = [];
            }

            $app->singleton($serviceName, function ($app) use ($class, $config) {
                return new $class($app['swap.http_client'], $app['swap.request_factory'], $config);
            });

            $app->tag($serviceName, 'swap.service');
        }
    }

    /**
     * Register the chain service.
     *
     * @param Application $app
     */
    private function registerChain(Application $app)
    {
        $app->singleton('swap.chain', function ($app) {
            $this->registerServices($app);

            return new Chain($app->tagged('swap.service'));
        });
    }

    /**
     * Register the cache item pool.
     *
     * @param Application $app
     */
    private function registerCacheItemPool(Application $app)
    {
        $app->singleton('swap.cache_item_pool', function ($app) {
            if ($cacheItemPool = $app->config->get('swap.cache_item_pool')) {
                return $app[$cacheItemPool];
            }

            return null;
        });
    }

    /**
     * Register the exchange rate provider.
     *
     * @param Application $app
     */
    public function registerExchangeRateProvider(Application $app)
    {
        $app->singleton('swap.exchange_rate_provider', function ($app) {
            return new Exchanger($app['swap.chain'], $app['swap.cache_item_pool'], $app->config->get('swap.options', []));
        });
    }

    /**
     * Registers Swap.
     *
     * @param Application $app
     */
    private function registerSwap(Application $app)
    {
        $app->singleton('swap', function ($app) {
            return new Swap($app['swap.exchange_rate_provider']);
        });

        $app->bind('Swap\Swap', 'swap');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'swap'
        ];
    }
}
