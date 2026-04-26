<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Factories;

use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Repositories\NullStateRepository;
use Aleoosha\HiveMind\Repositories\SwooleStateRepository;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Laravel\Octane\Facades\Octane;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class RepositoryFactory
{
    private static ?bool $storageAvailable = null;

    public function makeStateRepository(): StateRepositoryInterface
    {
        if ($this->isSwooleTableActive()) {
            return app(SwooleStateRepository::class);
        }

        return $this->isRedisReady() 
            ? app(RedisStateRepository::class) 
            : app(NullStateRepository::class);
    }

    private function isSwooleTableActive(): bool
    {
        try {
            return class_exists(Octane::class) && Octane::table('hive_telemetry');
        } catch (Throwable) {
            return false;
        }
    }

    private function isRedisReady(): bool
    {
        if (self::$storageAvailable !== null) return self::$storageAvailable;

        try {
            Redis::connection()->ping();
            return self::$storageAvailable = true;
        } catch (Throwable) {
            return self::$storageAvailable = false;
        }
    }

    public static function reset(): void { self::$storageAvailable = null; }
}
