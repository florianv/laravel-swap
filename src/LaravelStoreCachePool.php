<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel;

use Cache\Adapter\Common\AbstractCachePool;
use Illuminate\Contracts\Cache\Store;
use Psr\Cache\CacheItemInterface;

/**
 * Laravel Store PSR6 Cache Pool.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
class LaravelStoreCachePool extends AbstractCachePool
{
    /**
     * This value replaces NULL values that are considered
     * unexisting by Laravel.
     *
     * @cosnt
     */
    const NULL_VALUE = '__LARAVEL_NULL__';

    /**
     * The store.
     *
     * @var Store
     */
    private $store;

    /**
     * Constructor.
     *
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    protected function storeItemInCache(CacheItemInterface $item, $ttl)
    {
        if ($ttl < 0) {
            return false;
        }

        $ttl = null === $ttl ? 0 : $ttl / 60;

        if (null === $value = $item->get()) {
            $value = self::NULL_VALUE;
        }

        $this->store->put($item->getKey(), $value, $ttl);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchObjectFromCache($key)
    {
        $data = $this->store->get($key);
        $success = null !== $data;

        if (self::NULL_VALUE === $data) {
            $data = null;
        }

        return [$success, $data, []];
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllObjectsFromCache()
    {
        $this->store->flush();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearOneObjectFromCache($key)
    {
        if (null === $this->store->get($key)) {
            return true;
        }

        return $this->store->forget($key);
    }
}
