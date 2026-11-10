<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Cache Driver
|--------------------------------------------------------------------------
|
| This option controls the cache driver that will be used to store cached
| data for the application. By default, we will use the file cache driver
| which stores the cache in the storage/framework/cache directory.
|
| Supported: file, redis, database, memcached, dynamodb
|
*/

return [

    'default' => env('CACHE_DRIVER', 'file'),

    'stores' => [

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

    ],

];