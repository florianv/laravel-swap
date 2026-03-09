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
use Swap\Laravel\SwapServiceProvider;

abstract class AbstractTestCase extends AbstractPackageTestCase
{
    protected static function getServiceProviderClass(): string
    {
        return SwapServiceProvider::class;
    }
}
