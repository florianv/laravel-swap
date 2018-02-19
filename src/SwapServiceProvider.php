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
use Illuminate\Contracts\Container\Container;
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
        $this->publishes([$source => $this->getConfigPath('swap.php')]);
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
     * @param Container $app
     */
    private function registerHttp(Container $app)
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
     * @param Container $app
     */
    private function registerServices(Container $app)
    {
        foreach ($app->config->get('swap.services', []) as $name => $config) {
            if (false === $config) {
                continue;
            }

            $class = $this->getServiceClass($name);
            $serviceName = sprintf('swap.service.%s', $name);

            // The PhpArray service is a particular case
            if ('array' === $name) {
                $app->singleton($serviceName, function () use ($config) {
                    return new PhpArray($config);
                });

                $app->tag($serviceName, 'swap.service');

                continue;
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
     * @param Container $app
     */
    private function registerChain(Container $app)
    {
        $app->singleton('swap.chain', function ($app) {
            $this->registerServices($app);

            return new Chain($app->tagged('swap.service'));
        });
    }

    /**
     * Registers the cache.
     *
     * @param Container $app
     */
    private function registerCacheItemPool(Container $app)
    {
        $app->singleton('swap.cache_item_pool', function ($app) {
            if ($cacheItemPool = $app->config->get('swap.cache_item_pool')) {
                return $app[$cacheItemPool];
            }

            if (null === $cache = $app->config->get('swap.cache')) {
                return;
            }

            $repository = $app['cache']->store($cache);

            return new LaravelStoreCachePool($repository->getStore());
        });
    }

    /**
     * Register the exchange rate provider.
     *
     * @param Container $app
     */
    private function registerExchangeRateProvider(Container $app)
    {
        $app->singleton('swap.exchange_rate_provider', function ($app) {
            return new Exchanger($app['swap.chain'], $app['swap.cache_item_pool'], $app->config->get('swap.options', []));
        });
    }

    /**
     * Registers Swap.
     *
     * @param Container $app
     */
    private function registerSwap(Container $app)
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
            'swap',
        ];
    }

    /**
     * Gets the service class from its name.
     *
     * @param string $name
     *
     * @return string
     */
    private function getServiceClass($name)
    {
        // WebserviceX is a special case
        if ('webservicex' === $name) {
            $name = 'webservice_x';
        }

        $camelized = ucfirst(implode('', array_map('ucfirst', explode('_', $name))));

        return 'Exchanger\\Service\\'.$camelized;
    }

    /**
     * Gets the full path to the config.
     *
     * @param string $path
     *
     * @return string
     */
    private function getConfigPath($path = '')
    {
        return app()->basePath().'/config'.($path ? '/'.$path : $path);
    }
}
