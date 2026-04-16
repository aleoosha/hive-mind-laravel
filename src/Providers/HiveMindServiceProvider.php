<?php

namespace Aleoosha\HiveMind\Providers;

use Illuminate\Support\ServiceProvider;
use Aleoosha\HiveMind\Console\Commands\HivePulseCommand;
use Aleoosha\HiveMind\Http\Middleware\AltruismMiddleware;

class HiveMindServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Aleoosha\HiveMind\Contracts\Serializer::class, function ($app) {
            $format = config('hive-mind.broadcast.format', 'json');
            
            return match ($format) {
                'msgpack' => new \Aleoosha\HiveMind\Serializers\MsgPackSerializer(),
                default   => new \Aleoosha\HiveMind\Serializers\JsonSerializer(),
            };
        });
        $this->app->singleton(
            \Aleoosha\HiveMind\Contracts\StateRepository::class, 
            \Aleoosha\HiveMind\Repositories\RedisStateRepository::class
        );
    }


    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/hive-mind.php' => config_path('hive-mind.php'),
        ], 'hive-mind-config');

        $this->registerMiddleware();

        if ($this->app->runningInConsole()) {
            $this->commands([
                HivePulseCommand::class,
            ]);
        }
    }

    protected function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('hive.altruism', AltruismMiddleware::class);
    }
}
