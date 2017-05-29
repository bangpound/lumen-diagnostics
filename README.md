Lumen Diagnostics
=================

Lumen Diagnostics exposes Zend Diagnostics results as a Lumen controller and Artisan
command.

This README assumes knowledge of [the Zend Diagnostics README][Zend Diagnostics README].

[Zend Diagnostics README]: https://github.com/zendframework/ZendDiagnostics/blob/master/README.md

Installation
------------

```bash
composer require activecampaign/diagnostics
```

Configuration
-------------

Create a `diagnostics.php` configuration file in your application. It needs to have
three properties: `default_group`, `groups`, and `checks`.

```php
<?php

return [
    'default_group' => 'default',
    'groups' => [
        'default' => ['writable_directory', 'disk_usage', 'disk_free', 'pdo_connection', 'opcache_memory', 'redis'],
        'cli' => ['writable_directory', 'disk_usage', 'disk_free', 'pdo_connection'],
    ],
    'checks' => [
        'writable_directory' => [ZendDiagnostics\Check\DirWritable::class, [sys_get_temp_dir()]],
        'disk_free' => [ZendDiagnostics\Check\DiskFree::class, ['10G', sys_get_temp_dir()]],
        'disk_usage' => [ZendDiagnostics\Check\DiskUsage::class, [95, 100, '/']],
        'pdo_connection' => [
            ZendDiagnostics\Check\PDOCheck::class,
            [
                env('DB_CONNECTION') . ':' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
            ]
        ],
        'redis' => ZendDiagnostics\Check\Redis::class,
        'opcache_memory' => [ZendDiagnostics\Check\OpCacheMemory::class, [80, 95]],
    ]
];
```

The `checks` array is keyed by an arbitrary name for that check. The value is either
a simple class name (see `redis` in the example) or an array tuple of the class name and
the constructor parameters (see everything else in the example).

Usage
-----

The Artisan command is run with `php artisan monitor:health`. If the exit status is 0 then
all health checks are good. If the exit status is anything but 0 then there are failing
health checks.

The HTTP controller is accessible at `/health`. When it returns HTTP 200 then all health
checks are good. If it returns HTTP 502 then there are failing health checks.

Credits
-------

Parts of this project are taken from the MIT licensed [Liip Monitor Bundle for
Symfony][Liip Monitor Bundle].

[Liip Monitor Bundle]: https://github.com/liip/LiipMonitorBundle
