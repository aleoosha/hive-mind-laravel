<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Providers;

use Aleoosha\DssCore\DecisionEngine;
use Aleoosha\HiveMind\Console\Commands\HiveDebugChartCommand;
use Aleoosha\HiveMind\Console\Commands\HivePulseCommand;
use Aleoosha\HiveMind\Factories\RepositoryFactory;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;
use Aleoosha\HiveMind\Repositories\SwooleStateRepository;
use Aleoosha\HiveMind\Services\MetricsCollector;
use Aleoosha\Support\Types\FixedPoint;
use Aleoosha\TauPid\Contracts\DTO\MetricProfile;
use Aleoosha\TauPid\Contracts\DTO\PidSettings;
use Aleoosha\TauPid\Contracts\Enums\AggressionMode;
use Aleoosha\TauPid\Contracts\PidCalculatorInterface;
use Aleoosha\TauPid\Contracts\PidStateRepositoryInterface;
use Aleoosha\TauPid\Contracts\PidTunerInterface;
use Aleoosha\TauPid\Kernel\Services\PidCalculator;
use Aleoosha\TauPid\Kernel\Services\PidTuner;
use Aleoosha\Telemetry\Contracts\MetricsCollectorInterface;
use Aleoosha\Telemetry\Contracts\StateRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class HiveMindServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConsoleCommands();
        $this->loadPackageMigrations();
        $this->registerMiddlewareAlias();
        $this->offerPublishing();
        $this->configureOctaneIntegration();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/hive-mind.php', 'hive-mind');
        $this->registerInfrastructure();
        $this->registerTauKernel();
        $this->registerDecisionEngine();
    }

    private function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                HivePulseCommand::class,
                HiveDebugChartCommand::class,
            ]);
        }
    }

    private function loadPackageMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    private function registerMiddlewareAlias(): void
    {
        $this->app['router']->aliasMiddleware('hive.altruism', AltruismMiddleware::class);
    }

    private function offerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../../config/hive-mind.php' => config_path('hive-mind.php'),
        ], 'hive-mind-config');
    }

    private function configureOctaneIntegration(): void
    {
        $octaneEvent = 'Laravel\Octane\Events\RequestReceived';

        if (class_exists($octaneEvent)) {
            Event::listen($octaneEvent, fn () => RepositoryFactory::reset());
        }
    }

    private function registerInfrastructure(): void
    {
        $this->app->singleton(RepositoryFactory::class, RepositoryFactory::class);
        $this->app->singleton(SwooleStateRepository::class, SwooleStateRepository::class);
        $this->app->singleton(MetricsCollectorInterface::class, MetricsCollector::class);

        $this->app->bind(StateRepositoryInterface::class, function ($app) {
            return $app->make(RepositoryFactory::class)->makeStateRepository();
        });

        $this->app->bind(PidStateRepositoryInterface::class, function ($app) {
            return $app->make(RepositoryFactory::class)->makePidRepository();
        });
    }

    private function registerTauKernel(): void
    {
        $this->app->singleton(PidCalculatorInterface::class, PidCalculator::class);
        $this->app->singleton(PidTunerInterface::class, PidTuner::class);
    }

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

    private function buildMetricProfiles(): array
    {
        $config = config('hive-mind.thresholds', []);
        $profiles = [];

        foreach ($config as $key => $options) {
            $profiles[] = $this->makeProfile($key, $options);
        }

        return $profiles;
    }

    private function makeProfile(string $name, mixed $options): MetricProfile
    {
        $isArr = is_array($options);

        return new MetricProfile(
            metricName: $name,
            targetThreshold: FixedPoint::fromFloat((float) ($isArr ? ($options['limit'] ?? 0) : $options)),
            pidSettings: $this->getDefaultPidSettings(),
            settlingTimeSeconds: (int) ($isArr ? ($options['settling_time'] ?? 5) : 5),
            activationMargin: (float) ($isArr ? ($options['activation_margin'] ?? 0.9) : 0.9)
        );
    }

    private function getDefaultPidSettings(): PidSettings
    {
        $mode = AggressionMode::tryFrom(config('hive-mind.shedding.aggression', ''))
            ?? AggressionMode::BALANCED;

        $set = $mode->getSettings();

        return new PidSettings(
            kp: FixedPoint::fromFloat($set['kp']),
            ki: FixedPoint::fromFloat($set['ki']),
            kd: FixedPoint::fromFloat($set['kd']),
            antiWindup: FixedPoint::fromInt(1)
        );
    }
}
