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

use Illuminate\Contracts\Cache\Store;
use Psr\SimpleCache\CacheInterface;

/**
 * Laravel Simple Cache implementation.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
 */
final class LaravelSimpleCache implements CacheInterface
{
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
    public function get($key, $default = null)
    {
        $value = $this->store->get($key);

        return null === $value ? $default : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = null === $ttl ? 0 : $ttl / 60;

        $this->store->put($key, $value, $ttl);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->store->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->store->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $values = array_map(function ($value) use ($default) {
            return null === $value ? $default : $value;
        }, $this->store->many($keys));

        return array_combine($keys, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $ttl = null === $ttl ? 0 : $ttl / 60;

        $this->store->putMany($values, $ttl);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        $return = true;

        foreach ($keys as $key) {
            if (false === $this->store->forget($key)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return null === $this->store->get($key);
    }
}
