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

use GrahamCampbell\TestBenchCore\FacadeTrait;
use Swap\Laravel\Facades\Swap as SwapFacade;
use Swap\Laravel\Tests\AbstractTestCase;
use Swap\Swap;

final class SwapTest extends AbstractTestCase
{
    use FacadeTrait;

    /**
     * @psalm-pure
     */
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return 'swap';
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    protected static function getFacadeClass(): string
    {
        return SwapFacade::class;
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    protected static function getFacadeRoot(): string
    {
        return Swap::class;
    }
}
