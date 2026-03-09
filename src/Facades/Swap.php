<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Exchanger\Contract\ExchangeRate latest(string $currencyPair, array $options = [])
 * @method static \Exchanger\Contract\ExchangeRate historical(string $currencyPair, \DateTimeInterface $date, array $options = [])
 *
 * @see \Swap\Swap
 */
final class Swap extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'swap';
    }
}
