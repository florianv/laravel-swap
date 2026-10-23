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

use GrahamCampbell\TestBench\AbstractPackageTestCase;
use Illuminate\Foundation\Application;
use Swap\Laravel\SwapServiceProvider;

abstract class AbstractTestCase extends AbstractPackageTestCase
{
    /**
     * @psalm-pure
     */
    #[\Override]
    protected static function getServiceProviderClass(): string
    {
        return SwapServiceProvider::class;
    }

    /**
     * The application, which Testbench only sets up in setUp().
     */
    protected function application(): Application
    {
        self::assertNotNull($this->app);

        return $this->app;
    }

    protected function setConfig(string $key, mixed $value): void
    {
        $this->application()->make('config')->set($key, $value);
    }
}
