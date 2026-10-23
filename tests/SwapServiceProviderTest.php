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
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Swap\Laravel\SwapServiceProvider;
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

    public function testConfiguredCacheStoreIsUsed()
    {
        $store = new class extends ArrayStore {
            public function isEmpty(): bool
            {
                return [] === $this->storage;
            }
        };

        $this->app['cache']->extend('swap_test', function ($app) use ($store) {
            return $app['cache']->repository($store);
        });
        $this->app->config->set('cache.stores.swap_test', ['driver' => 'swap_test']);
        $this->app->config->set('swap.cache', 'swap_test');
        $this->app->config->set('swap.services', [
            'array' => [['EUR/USD' => 1.1]],
        ]);

        Assert::assertTrue($store->isEmpty());

        $rate = $this->app['swap']->latest('EUR/USD');

        Assert::assertSame(1.1, $rate->getValue());
        Assert::assertFalse($store->isEmpty());
    }

    public function testProviderIsDeferred()
    {
        $provider = new SwapServiceProvider($this->app);

        Assert::assertInstanceOf(DeferrableProvider::class, $provider);
        Assert::assertTrue($provider->isDeferred());
        Assert::assertSame(['swap', Swap::class], $provider->provides());
    }

    public function testSwapClassIsAnAliasOfTheSwapService()
    {
        Assert::assertSame($this->app['swap'], $this->app->make(Swap::class));
    }

    public function testConfigIsPublishedToTheApplicationConfigPath()
    {
        $paths = ServiceProvider::pathsToPublish(SwapServiceProvider::class);

        Assert::assertSame([$this->app->configPath('swap.php')], array_values($paths));
        Assert::assertSame(realpath(__DIR__ . '/../config/swap.php'), realpath((string) array_key_first($paths)));
    }

    public function testVendorPublishCopiesTheConfig()
    {
        $target = $this->app->configPath('swap.php');
        Assert::assertFileDoesNotExist($target);

        try {
            $this->artisan('vendor:publish', ['--provider' => SwapServiceProvider::class])->assertSuccessful();

            Assert::assertFileEquals(__DIR__ . '/../config/swap.php', $target);
        } finally {
            @unlink($target);
        }
    }

    public function testDefaultConfigIsMerged()
    {
        $this->app['swap'];

        Assert::assertSame(['european_central_bank' => true], array_filter($this->app->config->get('swap.services')));
        Assert::assertSame([], $this->app->config->get('swap.options'));
    }

    public function testConfiguredHttpClientAndRequestFactoryAreUsed()
    {
        $client = new RecordingClient();
        $requestFactory = new RecordingRequestFactory();

        $this->app->instance('test.http_client', $client);
        $this->app->instance('test.request_factory', $requestFactory);
        $this->app->config->set('swap.http_client', 'test.http_client');
        $this->app->config->set('swap.request_factory', 'test.request_factory');
        $this->app->config->set('swap.services', ['european_central_bank' => true]);

        try {
            $this->app['swap']->latest('EUR/USD');
            Assert::fail('The recording client never returns a response.');
        } catch (\Throwable) {
        }

        Assert::assertSame(['www.ecb.europa.eu'], $client->hosts);
        Assert::assertSame(1, $requestFactory->calls);
    }

    public function testServiceDisabledWithFalseIsNotAdded()
    {
        $this->app->config->set('swap.services', [
            'unknown' => false,
            'array' => [['EUR/USD' => 1.1]],
        ]);

        Assert::assertSame(1.1, $this->app['swap']->latest('EUR/USD')->getValue());
    }

    public function testServicesAreTriedInConfigOrder()
    {
        $client = new RecordingClient();
        $this->app->instance('test.http_client', $client);
        $this->app->config->set('swap.http_client', 'test.http_client');
        $this->app->config->set('swap.services', [
            'european_central_bank' => true,
            'array' => [['EUR/USD' => 1.1]],
        ]);

        $rate = $this->app['swap']->latest('EUR/USD');

        Assert::assertSame('array', $rate->getProviderName());
        Assert::assertSame(['www.ecb.europa.eu'], $client->hosts, 'The first configured service is tried first.');
    }

    public function testFirstConfiguredServiceAnswersFirst()
    {
        $client = new RecordingClient();
        $this->app->instance('test.http_client', $client);
        $this->app->config->set('swap.http_client', 'test.http_client');
        $this->app->config->set('swap.services', [
            'array' => [['EUR/USD' => 1.1]],
            'european_central_bank' => true,
        ]);

        $rate = $this->app['swap']->latest('EUR/USD');

        Assert::assertSame('array', $rate->getProviderName());
        Assert::assertSame([], $client->hosts, 'Later services are not called once a rate is found.');
    }
}

/**
 * Records the host of each request and fails it, like an unreachable provider.
 */
final class RecordingClient implements ClientInterface
{
    /** @var list<string> */
    public array $hosts = [];

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->hosts[] = $request->getUri()->getHost();

        throw new \RuntimeException('No network in tests.');
    }
}

/**
 * Counts the requests created through it.
 */
final class RecordingRequestFactory implements RequestFactoryInterface
{
    public int $calls = 0;

    public function createRequest(string $method, $uri): RequestInterface
    {
        ++$this->calls;

        return (new Psr17Factory())->createRequest($method, $uri);
    }
}
