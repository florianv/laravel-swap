<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Florianv\LaravelSwap\Tests\Facades;

use Florianv\LaravelSwap\Tests\AbstractTestCase;
use GrahamCampbell\TestBench\Traits\FacadeTestCaseTrait;

class SwapTest extends AbstractTestCase
{
    use FacadeTestCaseTrait;

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
        return 'Florianv\LaravelSwap\Facades\Swap';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFacadeRoot()
    {
        return 'Swap\Swap';
    }
}
