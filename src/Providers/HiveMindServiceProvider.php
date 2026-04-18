<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Providers;

use Illuminate\Support\ServiceProvider;
use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Contracts\PidStateRepository;
use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Repositories\RedisPidStateRepository;
use Aleoosha\HiveMind\Factories\SerializerFactory;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\HiveMind\Services\MetricsAccumulator;
use Aleoosha\HiveMind\Services\PidCalculator;
use Aleoosha\HiveMind\Services\SwarmIntelligence;
use Aleoosha\HiveMind\DTO\AccumulatorState;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Console\Commands\HivePulseCommand;

final class HiveMindServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/hive-mind.php', 'hive-mind');
        
        $this->registerBaseServices();
        $this->registerPidServices();
    }

    public function boot(): void
    {
        $this->registerResources();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishing();
        }

        $this->setupLifecycle();
    }

    private function registerBaseServices(): void
    {
        $this->app->singleton(Serializer::class, function ($app) {
            return (new SerializerFactory())->make($app);
        });

        $this->app->singleton(StateRepository::class, RedisStateRepository::class);
        $this->app->singleton(MetricsCollector::class);
    }

    private function registerPidServices(): void
    {
        $this->app->singleton(PidStateRepository::class, RedisPidStateRepository::class);
        $this->app->singleton(AccumulatorState::class);
        $this->app->singleton(MetricsAccumulator::class);
        $this->app->singleton(PidCalculator::class);
        $this->app->singleton(SwarmIntelligence::class);
    }

    private function registerResources(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->app['router']->aliasMiddleware('hive.altruism', AltruismMiddleware::class);
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'hive-mind-migrations');

        $this->publishes([
            __DIR__ . '/../../config/hive-mind.php' => config_path('hive-mind.php'),
        ], 'hive-mind-config');
    }

    private function registerCommands(): void
    {
        $this->commands([
            HivePulseCommand::class,
            HiveDebugChartCommand::class,
        ]);
    }

    private function setupLifecycle(): void
    {
        $this->app->terminating(function () {
            if ($this->app->bound(StateRepository::class)) {
                $this->app->make(StateRepository::class)->flushLocalCache();
            }
        });
    }
}
