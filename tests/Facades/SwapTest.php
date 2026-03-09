<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swap\Laravel\Tests\Facades;

use Swap\Laravel\Facades\Swap as SwapFacade;
use Swap\Laravel\Tests\AbstractTestCase;
use GrahamCampbell\TestBenchCore\FacadeTrait;
use Swap\Swap;

class SwapTest extends AbstractTestCase
{
    use FacadeTrait;

    protected static function getFacadeAccessor(): string
    {
        return 'swap';
    }

    protected static function getFacadeClass(): string
    {
        return SwapFacade::class;
    }

    protected static function getFacadeRoot(): string
    {
        return Swap::class;
    }
}
