<?php

namespace Aleoosha\HiveMind\Traits;

use Aleoosha\HiveMind\Contracts\StateRepository;
use Aleoosha\HiveMind\Exceptions\HiveOvercapacityException;
use Illuminate\Support\Facades\App;

trait AsHiveMember
{
    public static function bootAsHiveMember(): void
    {
        if (method_exists(static::class, 'saving')) {
            static::saving(function () {
                (new self)->guardAgainstHighLoad();
            });
        }
    }

    public function guardAgainstHighLoad(): void
    {
        $health = $this->getHiveHealth();
        
        if ($health >= config('hive-mind.shedding.activation_threshold', 75)) {
            throw new HiveOvercapacityException(
                health: $health,
                message: "Action declined to preserve cluster stability"
            );
        }
    }

    public function isHiveDistressed(): bool
    {
        return $this->getHiveHealth() >= config('hive-mind.shedding.activation_threshold', 75);
    }

    public function getHiveHealth(): int
    {
        return App::make(StateRepository::class)->getGlobalHealth();
    }
}
