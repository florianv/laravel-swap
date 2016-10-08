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

use Cache\IntegrationTests\CachePoolTest as BaseTest;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Swap\Laravel\LaravelStoreCachePool;

class LaravelStoreCachePoolTest extends BaseTest
{
    public function createCachePool()
    {
        return new LaravelStoreCachePool(
            new FileStore(
                new Filesystem(),
                sys_get_temp_dir().'/swap-laravel-tests'
            )
        );
    }
}
