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

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;
use PHPUnit\Framework\Assert;
use Swap\Laravel\SwapServiceProvider;
use Swap\Swap;

/**
 * Loads the provider the way an application does, through the provider manifest.
 *
 * Testbench registers package providers eagerly, so the other tests never see the deferred loading.
 */
final class DeferredProviderTest extends AbstractTestCase
{
    private ?string $manifestPath = null;

    #[\Override]
    protected function tearDown(): void
    {
        if (null !== $this->manifestPath) {
            @unlink($this->manifestPath);
        }

        parent::tearDown();
    }

    public function testProviderIsOnlyRegisteredWhenSwapIsResolved(): void
    {
        $this->loadDeferred();

        Assert::assertSame([], $this->application()->getProviders(SwapServiceProvider::class));

        $swap = $this->application()->make(Swap::class);

        Assert::assertCount(1, $this->application()->getProviders(SwapServiceProvider::class));
        Assert::assertSame($swap, $this->application()->make('swap'));
    }

    public function testConfigDefaultsAreMergedWhenSwapIsResolved(): void
    {
        $this->loadDeferred();
        $config = $this->application()->make('config');

        Assert::assertNull($config->get('swap.services'));

        $this->application()->make('swap');

        Assert::assertSame(['european_central_bank' => true], array_filter($config->get('swap.services')));
    }

    public function testApplicationBindingOfTheSwapServiceWins(): void
    {
        $this->loadDeferred();
        $custom = $this->createStub(Swap::class);
        $this->application()->singleton('swap', static fn(): Swap => $custom);

        Assert::assertSame($custom, $this->application()->make('swap'));
        Assert::assertSame($custom, $this->application()->make(Swap::class));
    }

    public function testApplicationBindingOfTheSwapClassWins(): void
    {
        $this->loadDeferred();
        $custom = $this->createStub(Swap::class);
        $this->application()->singleton(Swap::class, static fn(): Swap => $custom);

        $this->application()->make('swap');

        Assert::assertSame($custom, $this->application()->make(Swap::class));
    }

    public function testApplicationExtensionOfTheSwapClassIsApplied(): void
    {
        $this->loadDeferred();
        $decorated = $this->createStub(Swap::class);
        $this->application()->extend(Swap::class, static fn(): Swap => $decorated);

        Assert::assertSame($decorated, $this->application()->make(Swap::class));
    }

    public function testTestDoubleOfTheSwapClassWins(): void
    {
        $this->loadDeferred();
        $fake = $this->createStub(Swap::class);
        $this->application()->instance(Swap::class, $fake);

        $this->application()->make('swap');

        Assert::assertSame($fake, $this->application()->make(Swap::class));
    }

    public function testFacadeTestDoubleIsUsedForTheSwapClass(): void
    {
        $this->loadDeferred();
        $fake = $this->createStub(Swap::class);
        $this->application()->instance('swap', $fake);

        Assert::assertSame($fake, $this->application()->make(Swap::class));
    }

    /**
     * Registers nothing with Testbench, so the provider is only known through the manifest.
     *
     * @psalm-pure
     */
    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [];
    }

    private function loadDeferred(): void
    {
        $this->manifestPath = sys_get_temp_dir() . '/swap-manifest-' . bin2hex(random_bytes(8)) . '.php';

        (new ProviderRepository($this->application(), new Filesystem(), $this->manifestPath))
            ->load([SwapServiceProvider::class]);
    }
}
