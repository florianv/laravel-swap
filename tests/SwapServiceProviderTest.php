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
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Swap\Laravel\SwapServiceProvider;
use Swap\Swap;

final class SwapServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    public function testMissingServiceConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "access_key" option must be provided.');

        $this->setConfig('swap.services', [
            'currency_layer' => true,
        ]);

        $this->resolveSwap();
    }

    public function testUnknownService(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The service "unknown" is not registered.');

        $this->setConfig('swap.services', [
            'unknown' => true,
        ]);

        $this->resolveSwap();
    }

    public function testEmptyServices(): void
    {
        $this->setConfig('swap.services', []);

        Assert::assertInstanceOf(Swap::class, $this->resolveSwap());
    }

    public function testSwapIsInjectable(): void
    {
        $this->assertIsInjectable(Swap::class);
    }

    public function testOptions(): void
    {
        $this->setConfig('swap.options', ['cache_ttl' => 60]);

        Assert::assertInstanceOf(Swap::class, $this->resolveSwap());
    }

    public function testConfiguredCacheStoreIsUsed(): void
    {
        $store = new class extends ArrayStore {
            /**
             * @psalm-mutation-free
             */
            public function isEmpty(): bool
            {
                return [] === $this->storage;
            }
        };

        $this->application()->make('cache')->extend('swap_test', function (Application $app) use ($store) {
            return $app->make('cache')->repository($store);
        });
        $this->setConfig('cache.stores.swap_test', ['driver' => 'swap_test']);
        $this->setConfig('swap.cache', 'swap_test');
        $this->setConfig('swap.services', [
            'array' => [['EUR/USD' => 1.1]],
        ]);

        Assert::assertTrue($store->isEmpty());

        $rate = $this->resolveSwap()->latest('EUR/USD');

        Assert::assertSame(1.1, $rate->getValue());
        Assert::assertFalse($store->isEmpty());
    }

    public function testProviderIsDeferred(): void
    {
        Assert::assertTrue((new SwapServiceProvider($this->application()))->isDeferred());
    }

    public function testApplicationBindingOfTheSwapClassWins(): void
    {
        $fake = $this->createStub(Swap::class);
        $this->application()->instance(Swap::class, $fake);

        $this->resolveSwap();

        Assert::assertSame($fake, $this->application()->make(Swap::class));
    }

    public function testApplicationBindingOfTheSwapServiceWins(): void
    {
        $custom = $this->createStub(Swap::class);
        $this->application()->singleton('swap', static fn(): Swap => $custom);

        Assert::assertSame($custom, $this->application()->make('swap'));
        Assert::assertSame($custom, $this->application()->make(Swap::class));
    }

    public function testInvalidServiceNameOptionFailsLoudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "swap.cache" option must be a name, bool given.');

        $this->setConfig('swap.cache', true);

        $this->resolveSwap();
    }

    public function testSwapClassResolvesTheSwapService(): void
    {
        Assert::assertSame($this->resolveSwap(), $this->application()->make(Swap::class));
    }

    public function testConfigIsPublishedToTheApplicationConfigPath(): void
    {
        $paths = ServiceProvider::pathsToPublish(SwapServiceProvider::class);

        Assert::assertSame([$this->application()->configPath('swap.php')], array_values($paths));
        Assert::assertSame(realpath(__DIR__ . '/../config/swap.php'), realpath((string) array_key_first($paths)));
    }

    public function testVendorPublishCopiesTheConfig(): void
    {
        $target = $this->application()->configPath('swap.php');
        Assert::assertFileDoesNotExist($target);

        try {
            $exitCode = $this->withoutMockingConsoleOutput()->artisan('vendor:publish', ['--provider' => SwapServiceProvider::class]);

            Assert::assertSame(0, $exitCode);
            Assert::assertFileEquals(__DIR__ . '/../config/swap.php', $target);
        } finally {
            @unlink($target);
        }
    }

    public function testDefaultConfigIsMerged(): void
    {
        $this->resolveSwap();

        $config = $this->application()->make('config');

        Assert::assertSame(['european_central_bank' => true], array_filter($config->get('swap.services')));
        Assert::assertSame([], $config->get('swap.options'));
    }

    public function testConfiguredHttpClientAndRequestFactoryAreUsed(): void
    {
        $client = new RecordingClient();
        $requestFactory = new RecordingRequestFactory();

        $this->application()->instance('test.http_client', $client);
        $this->application()->instance('test.request_factory', $requestFactory);
        $this->setConfig('swap.http_client', 'test.http_client');
        $this->setConfig('swap.request_factory', 'test.request_factory');
        $this->setConfig('swap.services', ['european_central_bank' => true]);

        try {
            $this->resolveSwap()->latest('EUR/USD');
            Assert::fail('The recording client never returns a response.');
        } catch (\Exception) {
        }

        Assert::assertSame(['www.ecb.europa.eu'], $client->hosts);
        Assert::assertSame(1, $requestFactory->calls);
    }

    public function testServiceDisabledWithFalseIsNotAdded(): void
    {
        $this->setConfig('swap.services', [
            'unknown' => false,
            'array' => [['EUR/USD' => 1.1]],
        ]);

        Assert::assertSame(1.1, $this->resolveSwap()->latest('EUR/USD')->getValue());
    }

    public function testServicesAreTriedInConfigOrder(): void
    {
        $client = new RecordingClient();
        $this->application()->instance('test.http_client', $client);
        $this->setConfig('swap.http_client', 'test.http_client');
        $this->setConfig('swap.services', [
            'european_central_bank' => true,
            'array' => [['EUR/USD' => 1.1]],
        ]);

        $rate = $this->resolveSwap()->latest('EUR/USD');

        Assert::assertSame('array', $rate->getProviderName());
        Assert::assertSame(['www.ecb.europa.eu'], $client->hosts, 'The first configured service is tried first.');
    }

    public function testFirstConfiguredServiceAnswersFirst(): void
    {
        $client = new RecordingClient();
        $this->application()->instance('test.http_client', $client);
        $this->setConfig('swap.http_client', 'test.http_client');
        $this->setConfig('swap.services', [
            'array' => [['EUR/USD' => 1.1]],
            'european_central_bank' => true,
        ]);

        $rate = $this->resolveSwap()->latest('EUR/USD');

        Assert::assertSame('array', $rate->getProviderName());
        Assert::assertSame([], $client->hosts, 'Later services are not called once a rate is found.');
    }

    private function resolveSwap(): Swap
    {
        $swap = $this->application()->make('swap');
        Assert::assertInstanceOf(Swap::class, $swap);

        return $swap;
    }
}

/**
 * Records the host of each request and fails it, like an unreachable provider.
 */
final class RecordingClient implements ClientInterface
{
    /** @var list<string> */
    public array $hosts = [];

    #[\Override]
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

    #[\Override]
    public function createRequest(string $method, $uri): RequestInterface
    {
        ++$this->calls;

        return (new Psr17Factory())->createRequest($method, $uri);
    }
}
