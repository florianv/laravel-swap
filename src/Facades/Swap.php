<?php

/*
 * This file is part of Laravel Swap.
 *
 * @method static \Exchanger\Contract\ExchangeRate latest (string $currencyPair, array $options = [])
 * @method static \Exchanger\Contract\ExchangeRate historical (string $currencyPair, \DateTimeInterface $date, array $options = [])
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for Swap.
 *
 * @author Florian Voutzinos <florian@voutzinos.com>
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
