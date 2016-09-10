<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Florianv\LaravelSwap\Tests;

use GrahamCampbell\TestBench\Traits\ServiceProviderTestCaseTrait;
use Ivory\HttpAdapter\CurlHttpAdapter;

class SwapServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTestCaseTrait;

    public function testDefauktHttpAdapter()
    {
        $this->assertInstanceOf('Ivory\HttpAdapter\FileGetContentsHttpAdapter', $this->app['swap.http_adapter']);
    }

    public function testCustomHttpAdapter()
    {
        $adapter = new CurlHttpAdapter();
        $this->app->singleton('swap.http_adapter_curl', function () use ($adapter) {
            return $adapter;
        });

        $this->app->config->set('swap.http_adapter', 'swap.http_adapter_curl');
        $this->assertSame($adapter, $this->app['swap.http_adapter']);
    }

    public function testDefaultCache()
    {
        $this->assertNull($this->app['swap.cache']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown cache type "unknown".
     */
    public function testUnknownCache()
    {
        $this->app->config->set('swap.cache', [
            'type' => 'unknown',
        ]);
        $this->app['swap.cache'];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cache store [not_found] is not defined
     */
    public function testIlluminateCacheStoreNotFound()
    {
        $this->app->config->set('swap.cache', [
            'type' => 'illuminate',
            'store' => 'not_found',
            'ttl' => 60,
        ]);
        $this->app['swap.cache'];
    }

    public function testIlluminateCache()
    {
        $this->app->config->set('cache', [
            'stores' => [
                'array' => [
                    'driver' => 'array',
                ],
            ],
        ]);

        $this->app->config->set('swap.cache', [
            'type' => 'illuminate',
            'store' => 'array',
            'ttl' => 60,
        ]);

        $this->assertInstanceOf('Swap\Cache\IlluminateCache', $this->app['swap.cache']);
    }

    public function testDefaultProvider()
    {
        $this->assertInstanceOf('Swap\Provider\ChainProvider', $this->app['swap.provider']);
    }

    public function testAllProviders()
    {
        $this->app->config->set('swap.providers', [
            'yahoo_finance' => true,
            'google_finance' => true,
            'european_central_bank' => true,
            'national_bank_of_romania' => true,
            'webservicex' => true,
            'open_exchange_rates' => [
                'app_id' => 'foo',
                'enterprise' => true,
            ],
            'xignite' => [
                'token' => 'bar',
            ],
            'central_bank_of_republic_turkey' => true,
            'central_bank_of_czech_republic' => true,
        ]);

        $this->assertInstanceOf('Swap\Provider\ChainProvider', $this->app['swap.provider']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unknown provider with name "bankster"
     */
    public function testUnknownProvider()
    {
        $this->app->config->set('swap.providers', [
            'bankster' => true,
        ]);

        $this->app['swap.provider'];
    }

    public function testSwapIsInjectable()
    {
        $this->assertIsInjectable('Swap\SwapInterface');
        $this->assertIsInjectable('Swap\Swap');
    }
}
