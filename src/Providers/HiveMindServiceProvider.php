<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Providers;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Console\Commands\HiveDebugChartCommand;
use Aleoosha\HiveMind\Console\Commands\HivePulseCommand;
use Aleoosha\HiveMind\Factories\SerializerFactory;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Repositories\RedisPidStateRepository;
use Aleoosha\HiveMind\Repositories\RedisStateRepository;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\MetricProfile;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\PidCalculatorInterface;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\TauPid\Contracts\PidTunerInterface;
use Aleoosha\TauPid\Kernel\Services\PidCalculator;
use Aleoosha\TauPid\Kernel\Services\PidTuner;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class HiveMindServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register console commands if running in CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                HivePulseCommand::class,
                HiveDebugChartCommand::class,
            ]);
        }

        // Load migrations from the package
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register middleware alias
        $this->app['router']->aliasMiddleware('hive.altruism', AltruismMiddleware::class);

        // Register config publishing
        $this->publishes([
            __DIR__.'/../../config/hive-mind.php' => config_path('hive-mind.php'),
        ], 'hive-mind-config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/hive-mind.php', 'hive-mind');

        $this->registerInfrastructure();
        $this->registerTauKernel();
        $this->registerDecisionEngine();
    }

    /**
     * Register base telemetry and storage services.
     */
    private function registerInfrastructure(): void
    {
        $this->app->singleton(SerializerInterface::class, function ($app) {
            return (new SerializerFactory)->make($app);
        });

        $this->app->singleton(StateRepositoryInterface::class, RedisStateRepository::class);
        $this->app->singleton(PidStateRepositoryInterface::class, RedisPidStateRepository::class);
        $this->app->singleton(MetricsCollectorInterface::class, MetricsCollector::class);
    }

    /**
     * Register mathematical PID calculation and tuning kernels.
     */
    private function registerTauKernel(): void
    {
        $this->app->singleton(PidCalculatorInterface::class, PidCalculator::class);
        $this->app->singleton(PidTunerInterface::class, PidTuner::class);
    }

    /**
     * Register the central Decision Support System (The Brain).
     */
    private function registerDecisionEngine(): void
    {
        $this->app->singleton(DecisionEngine::class, function ($app) {
            return new DecisionEngine(
                $app->make(PidCalculatorInterface::class),
                $app->make(PidTunerInterface::class),
                $app->make(PidStateRepositoryInterface::class),
                $this->buildMetricProfiles()
            );
        });
    }

    /**
     * Transform Laravel configuration into DTO profiles for DSS Core.
     *
     * @return MetricProfile[]
     */
    private function buildMetricProfiles(): array
    {
        $config = config('hive-mind.thresholds', []);
        $profiles = [];

        foreach ($config as $key => $threshold) {
            $profiles[] = new MetricProfile(
                metricName: $key,
                targetThreshold: FixedPoint::fromFloat((float) $threshold),
                pidSettings: $this->getDefaultPidSettings()
            );
        }

        return $profiles;
    }

    /**
     * Get default PID coefficients as DTO.
     */
    private function getDefaultPidSettings(): PidSettings
    {
        return new PidSettings(
            kp: FixedPoint::fromFloat(2.0), 
            ki: FixedPoint::fromFloat(0.5),
            kd: FixedPoint::fromFloat(1.0),
            antiWindup: FixedPoint::fromInt(1)
        );
    }
}
