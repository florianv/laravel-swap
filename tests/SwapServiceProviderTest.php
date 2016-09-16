<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel\Tests;

use Exchanger\ExchangeRate;
use GrahamCampbell\TestBench\Traits\ServiceProviderTestCaseTrait;
use Exchanger\Service\Chain;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Cache\CacheItemPoolInterface;
use Swap\Swap;

class SwapServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTestCaseTrait;

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "access_key" option must be provided.
     */
    public function testMissingServiceConfig()
    {
        $this->app->config->set('swap.services', [
            'currencylayer' => true,
        ]);

        $this->app['swap'];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The service "unknown" does not exist.
     */
    public function testUnknownService()
    {
        $this->app->config->set('swap.services', [
            'unknown' => true,
        ]);

        $this->app['swap'];
    }

    public function testEmptyServices()
    {
        $this->app['swap'];
    }

    public function testAllServices()
    {
        $this->app->config->set('swap.services', [
            'central_bank_of_czech_republic' => true,
            'central_bank_of_republic_turkey' => true,
            'currencylayer' => ['access_key' => 'secret', 'enterprise' => false],
            'european_central_bank' => true,
            'fixer' => true,
            'google' => true,
            'national_bank_of_romania' => true,
            'open_exchange_rates' => ['app_id' => 'secret', 'enterprise' => false],
            'array' => [['EUR/USD' => new ExchangeRate('1.5')]],
            'webservicex' => true,
            'xignite' => ['token' => 'token'],
            'yahoo' => true
        ]);

        $this->assertInstanceOf(Chain::class, $this->app['swap.chain']);
    }

    public function testSwapIsInjectable()
    {
        $this->assertIsInjectable(Swap::class);
    }

    public function testOptions()
    {
        $this->app->config->set('swap.options', ['cache_ttl' => 60]);
        $this->assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomHttpClient()
    {
        $client = HttpClientDiscovery::find();

        $this->app->singleton('http_client_custom', function () use ($client) {
            return $client;
        });

        $this->app->config->set('swap.http_client', 'http_client_custom');

        $this->assertSame($client, $this->app['swap.http_client']);
        $this->assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomRequestFactory()
    {
        $requestFactory = MessageFactoryDiscovery::find();

        $this->app->singleton('request_factory_custom', function () use ($requestFactory) {
            return $requestFactory;
        });

        $this->app->config->set('swap.request_factory', 'request_factory_custom');

        $this->assertSame($requestFactory, $this->app['swap.request_factory']);
        $this->assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomCacheItemPool()
    {
        $cacheItemPool = $this->getMock(CacheItemPoolInterface::class);

        $this->app->singleton('cache_item_pool_custom', function () use ($cacheItemPool) {
            return $cacheItemPool;
        });

        $this->app->config->set('swap.cache_item_pool', 'cache_item_pool_custom');

        $this->assertSame($cacheItemPool, $this->app['swap.cache_item_pool']);
        $this->assertInstanceOf(Swap::class, $this->app['swap']);
    }
}
