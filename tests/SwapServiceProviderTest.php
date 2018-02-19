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

use Exchanger\CurrencyPair;
use Exchanger\ExchangeRate;
use Exchanger\ExchangeRateQuery;
use Exchanger\HistoricalExchangeRateQuery;
use Exchanger\Service\CentralBankOfCzechRepublic;
use Exchanger\Service\CentralBankOfRepublicTurkey;
use Exchanger\Service\CurrencyDataFeed;
use Exchanger\Service\CurrencyLayer;
use Exchanger\Service\EuropeanCentralBank;
use Exchanger\Service\Fixer;
use Exchanger\Service\Forge;
use Exchanger\Service\Google;
use Exchanger\Service\NationalBankOfRomania;
use Exchanger\Service\OpenExchangeRates;
use Exchanger\Service\PhpArray;
use Exchanger\Service\RussianCentralBank;
use Exchanger\Service\WebserviceX;
use Exchanger\Service\Xignite;
use Exchanger\Service\Yahoo;
use GrahamCampbell\TestBench\Traits\ServiceProviderTestCaseTrait;
use Exchanger\Service\Chain;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Psr\Cache\CacheItemPoolInterface;
use Swap\Laravel\LaravelStoreCachePool;
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
            'currency_layer' => true,
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
            'currency_layer' => ['access_key' => 'secret', 'enterprise' => false],
            'european_central_bank' => true,
            'fixer' => true,
            'google' => true,
            'national_bank_of_romania' => true,
            'open_exchange_rates' => ['app_id' => 'secret', 'enterprise' => false],
            'array' => [['EUR/USD' => new ExchangeRate('1.5')]],
            'webservicex' => true,
            'xignite' => ['token' => 'token'],
            'yahoo' => true,
            'russian_central_bank' => true,
            'currency_data_feed' => ['api_key' => 'secret'],
            'forge' => ['api_key' => 'secret']
        ]);

        $this->assertInstanceOf(Chain::class, $this->app['swap.chain']);

        $services = $this->app->tagged('swap.service');

        $this->assertCount(15, $services);

        $this->assertInstanceOf(CentralBankOfCzechRepublic::class, $services[0]);
        $this->assertInstanceOf(CentralBankOfRepublicTurkey::class, $services[1]);
        $this->assertInstanceOf(CurrencyLayer::class, $services[2]);
        $this->assertInstanceOf(EuropeanCentralBank::class, $services[3]);
        $this->assertInstanceOf(Fixer::class, $services[4]);
        $this->assertInstanceOf(Google::class, $services[5]);
        $this->assertInstanceOf(NationalBankOfRomania::class, $services[6]);
        $this->assertInstanceOf(OpenExchangeRates::class, $services[7]);
        $this->assertInstanceOf(PhpArray::class, $services[8]);
        $this->assertInstanceOf(WebserviceX::class, $services[9]);
        $this->assertInstanceOf(Xignite::class, $services[10]);
        $this->assertInstanceOf(Yahoo::class, $services[11]);
        $this->assertInstanceOf(RussianCentralBank::class, $services[12]);
        $this->assertInstanceOf(CurrencyDataFeed::class, $services[13]);
        $this->assertInstanceOf(Forge::class, $services[14]);
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

    public function testCustomCache()
    {
        $this->app->config->set('swap.cache', 'file');

        $this->assertInstanceOf(LaravelStoreCachePool::class, $this->app['swap.cache_item_pool']);
        $this->assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testTaggedService()
    {
        $this->app->singleton('service_custom', function ($app) {
            return new Fixer($app['swap.http_client'], $app['swap.request_factory']);
        });

        $this->app->tag('service_custom', 'swap.service');

        $chain = $this->app['swap.chain'];

        $this->assertTrue($chain->supportQuery(new ExchangeRateQuery(CurrencyPair::createFromString('EUR/USD'))));
        $this->assertTrue($chain->supportQuery(new HistoricalExchangeRateQuery(CurrencyPair::createFromString('EUR/USD'), (new \DateTime())->modify('-15 days'))));
    }
}
