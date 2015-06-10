<?php

/*
 * This file is part of Laravel Swap.
 *
 * (c) Florian Voutzinos <florian@voutzinos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Http Adapter
    |--------------------------------------------------------------------------
    |
    | This option specifies a service id to use as http adapter
    | (defaults to FileGetContentsHttpAdapter).
    |
    */
    'http_adapter' => null,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | This option specifies the providers to use with their name as key and
    | their config as value. The providers will be wrapped in a ChainProvider
    | in the order they appear in this array.
    |
    | Here is the config spec for each provider:
    |
    | * "yahoo_finance", "google_finance", "european_central_bank", "webservicex"
    |   "national_bank_of_romania" can be enabled with "true" as value.
    |
    | * 'open_exchange_rates' => [
    |       'app_id' => 'secret', // Your app id
    |       'enterprise' => true, // True if your AppId is an enterprise one
    |   ]
    |
    | * 'xignite' => [
    |       'token' => 'secret', // The API token
    |   ]
    |
    */

    'providers' => [
        'yahoo_finance' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | This option specifies which cache to use to store rate values and its ttl.
    | Currently only Illuminate cache is supported:
    |
    | 'cache' => [
    |    'type' => 'illuminate',
    |    'store' => 'apc', // Name of the cache store
    |    'ttl' => 60 // Ttl in minutes (defaults to 0)
    | ],
    */
    'cache' => null,

];
