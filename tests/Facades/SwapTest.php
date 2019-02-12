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

    /**
     * {@inheritdoc}
     */
    protected function getFacadeAccessor()
    {
        return 'swap';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFacadeClass()
    {
        return SwapFacade::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFacadeRoot()
    {
        return Swap::class;
    }
}
