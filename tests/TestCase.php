<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Tests;

use Aleoosha\HiveMind\Providers\HiveMindServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HiveMindServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('hive-mind.shedding.activation_threshold', 75);
        $app['config']->set('hive-mind.shedding.retry_after', 60);
        $app['config']->set('database.redis.options.prefix', 'test_');
    }
}
