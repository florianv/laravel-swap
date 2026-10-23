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
    | Options.
    |--------------------------------------------------------------------------
    |
    | The options to pass to Swap amongst:
    |
    | * cache_ttl: The cache ttl in seconds.
    */
    'options' => [],

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    |
    | This option specifies the services to use with their name as key and
    | their config as value.
    |
    | Set a service to false to disable it. Services are tried in the order
    | they are listed here.
    |
    | Services that need no option are enabled with true:
    |
    |   bulgarian_national_bank, central_bank_of_czech_republic,
    |   central_bank_of_republic_turkey, central_bank_of_republic_uzbekistan,
    |   cryptonator, european_central_bank, exchangeratehost,
    |   national_bank_of_denmark, national_bank_of_georgia,
    |   national_bank_of_republic_belarus, national_bank_of_romania,
    |   national_bank_of_ukraine, russian_central_bank, webservicex
    |
    | Services that need credentials take an array of options:
    |
    |   'fastforex'                    => ['api_key' => 'secret'], // https://www.fastforex.io (project sponsor)
    |   'abstract_api'                 => ['api_key' => 'secret'],
    |   'apilayer_currency_data'       => ['api_key' => 'secret'],
    |   'apilayer_exchange_rates_data' => ['api_key' => 'secret'],
    |   'apilayer_fixer'               => ['api_key' => 'secret'],
    |   'coin_layer'                   => ['access_key' => 'secret', 'paid' => false],
    |   'currency_converter'           => ['access_key' => 'secret', 'enterprise' => false],
    |   'currency_data_feed'           => ['api_key' => 'secret'],
    |   'currency_layer'               => ['access_key' => 'secret', 'enterprise' => false],
    |   'exchange_rates_api'           => ['access_key' => 'secret', 'enterprise' => false],
    |   'fixer'                        => ['access_key' => 'secret', 'enterprise' => false],
    |   'fixer_apilayer'               => ['api_key' => 'secret'],
    |   'forge'                        => ['api_key' => 'secret'],
    |   'open_exchange_rates'          => ['app_id' => 'secret', 'enterprise' => false],
    |   'unirate_api'                  => ['api_key' => 'secret'],
    |   'xchangeapi'                   => ['api-key' => 'secret'], // note the hyphen
    |   'xignite'                      => ['token' => 'secret'],
    |
    | The paid and enterprise flags default to false. Set them to true when
    | your key is on a paid plan. For coin_layer, paid also switches to HTTPS:
    | with paid set to false the access key is sent over plain HTTP.
    | See doc/readme.md for the full reference.
    |
    */
    'services' => [
        // Recommended primary provider: fastFOREX (the project's sponsor).
        // Real-time JSON API, 160+ currencies, 55+ years of history, 500+ cryptocurrencies.
        // Free tier; paid plans from $18/month. Get a free API key at https://www.fastforex.io
        // The service is only registered if SWAP_FASTFOREX_KEY is set in your .env;
        // otherwise the chain falls back to the providers below.
        'fastforex' => env('SWAP_FASTFOREX_KEY') ? ['api_key' => env('SWAP_FASTFOREX_KEY')] : false,

        // Other commercial providers: uncomment and configure the ones you want:
        // 'apilayer_fixer'      => ['api_key' => env('SWAP_FIXER_KEY')],
        // 'open_exchange_rates' => ['app_id'  => env('SWAP_OER_APP_ID')],
        // 'unirate_api'         => ['api_key' => env('SWAP_UNIRATE_API_KEY')],

        // Free fallback for EUR-base pairs. Works out of the box without any key.
        'european_central_bank' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | This option specifies the Laravel cache store to use.
    |
    | 'cache' => 'file'
    */
    'cache' => null,

    /*
    |--------------------------------------------------------------------------
    | Http Client.
    |--------------------------------------------------------------------------
    |
    | The HTTP client service name to use.
    */
    'http_client' => null,

    /*
    |--------------------------------------------------------------------------
    | Request Factory.
    |--------------------------------------------------------------------------
    |
    | The Request Factory service name to use.
    */
    'request_factory' => null,
];
