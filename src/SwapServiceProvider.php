<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Florianv\LaravelSwap;

use Ivory\HttpAdapter\FileGetContentsHttpAdapter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Swap\Cache\IlluminateCache;
use Swap\Provider\CentralBankOfRepublicTurkeyProvider;
use Swap\Provider\CentralBankOfCzechRepublicProvider;
use Swap\Provider\ChainProvider;
use Swap\Provider\EuropeanCentralBankProvider;
use Swap\Provider\GoogleFinanceProvider;
use Swap\Provider\NationalBankOfRomaniaProvider;
use Swap\Provider\OpenExchangeRatesProvider;
use Swap\Provider\WebserviceXProvider;
use Swap\Provider\XigniteProvider;
use Swap\Provider\YahooFinanceProvider;
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
        $this->registerHttpAdapter($this->app);
        $this->registerProvider($this->app);
        $this->registerCache($this->app);
        $this->registerSwap($this->app);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'swap', 'swap.provider', 'swap.http_adapter', 'swap.cache',
        ];
    }

    /**
     * Registers the Http adapter.
     *
     * @param Application $app
     */
    private function registerHttpAdapter(Application $app)
    {
        $app->singleton('swap.http_adapter', function ($app) {
            $adapter = $app['config']['swap.http_adapter'];

            return null === $adapter ? new FileGetContentsHttpAdapter() : $app[$adapter];
        });
    }

    /**
     * Registers the provider.
     *
     * @param Application $app
     */
    private function registerProvider(Application $app)
    {
        $app->singleton('swap.provider', function ($app) {
            $providers = [];

            foreach ($app['config']['swap.providers'] as $providerName => $providerConfig) {
                switch ($providerName) {
                    case 'yahoo_finance':
                        $providers[] = new YahooFinanceProvider($app['swap.http_adapter']);
                        break;
                    case 'google_finance':
                        $providers[] = new GoogleFinanceProvider($app['swap.http_adapter']);
                        break;
                    case 'european_central_bank':
                        $providers[] = new EuropeanCentralBankProvider($app['swap.http_adapter']);
                        break;
                    case 'national_bank_of_romania':
                        $providers[] = new NationalBankOfRomaniaProvider($app['swap.http_adapter']);
                        break;
                    case 'webservicex':
                        $providers[] = new WebserviceXProvider($app['swap.http_adapter']);
                        break;
                    case 'open_exchange_rates':
                        $providers[] = new OpenExchangeRatesProvider(
                            $app['swap.http_adapter'],
                            $providerConfig['app_id'],
                            isset($providerConfig['enterprise']) ? $providerConfig['enterprise'] : false
                        );
                        break;
                    case 'xignite':
                        $providers[] = new XigniteProvider(
                            $app['swap.http_adapter'],
                            $providerConfig['token']
                        );
                        break;
                    case 'central_bank_of_republic_turkey':
                        $providers[] = new CentralBankOfRepublicTurkeyProvider($app['swap.http_adapter']);
                        break;
                    case 'central_bank_of_czech_republic':
                        $providers[] = new CentralBankOfCzechRepublicProvider($app['swap.http_adapter']);
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unknown provider with name "%s".', $providerName));
                }
            }

            return new ChainProvider($providers);
        });
    }

    /**
     * Registers the cache.
     *
     * @param Application $app
     */
    private function registerCache(Application $app)
    {
        $app->singleton('swap.cache', function ($app) {
            if (null === $cacheConfig = $app['config']['swap.cache']) {
                return;
            }

            if ('illuminate' === $cacheConfig['type']) {
                $repository = $app['cache']->store($cacheConfig['store']);

                return new IlluminateCache(
                    $repository->getStore(),
                    isset($cacheConfig['ttl']) ? $cacheConfig['ttl'] : 0
                );
            }

            throw new \RuntimeException(sprintf('Unknown cache type "%s".', $cacheConfig['type']));
        });
    }

    /**
     * Registers the Swap service.
     *
     * @param Application $app
     */
    private function registerSwap(Application $app)
    {
        $app->singleton('swap', function ($app) {
            return new Swap($app['swap.provider'], $app['swap.cache']);
        });

        $app->bind('Swap\Swap', 'swap');
        $app->bind('Swap\SwapInterface', 'swap');
    }
}
