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

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;
use Http\Discovery\HttpClientDiscovery;
use Swap\Swap;
use PHPUnit\Framework\Assert;
use Http\Discovery\Psr17FactoryDiscovery;

class SwapServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    public function testMissingServiceConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "access_key" option must be provided.');

        $this->app->config->set('swap.services', [
            'currency_layer' => true,
        ]);

        $this->app['swap'];
    }

    public function testUnknownService()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The service "unknown" is not registered.');

        $this->app->config->set('swap.services', [
            'unknown' => true,
        ]);

        $this->app['swap'];
    }

    public function testEmptyServices()
    {
        Assert::assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testSwapIsInjectable()
    {
        $this->assertIsInjectable(Swap::class);
    }

    public function testOptions()
    {
        $this->app->config->set('swap.options', ['cache_ttl' => 60]);

        Assert::assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomHttpClient()
    {
        $client = HttpClientDiscovery::find();

        $this->app->singleton('http_client_custom', function () use ($client) {
            return $client;
        });

        $this->app->config->set('swap.http_client', 'http_client_custom');

        Assert::assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomRequestFactory()
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $this->app->singleton('request_factory_custom', function () use ($requestFactory) {
            return $requestFactory;
        });

        $this->app->config->set('swap.request_factory', 'request_factory_custom');

        Assert::assertInstanceOf(Swap::class, $this->app['swap']);
    }

    public function testCustomLaravelCache()
    {
        $this->app->config->set('swap.cache', 'file');

        Assert::assertInstanceOf(Swap::class, $this->app['swap']);
    }
}
