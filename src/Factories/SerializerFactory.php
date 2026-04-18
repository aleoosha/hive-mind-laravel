<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Factories;

use Aleoosha\HiveMind\Contracts\Serializer;
use Aleoosha\HiveMind\Serializers\JsonSerializer;
use Aleoosha\HiveMind\Serializers\MsgPackSerializer;
use Illuminate\Contracts\Container\Container;

final class SerializerFactory
{
    /**
     * Создает экземпляр сериализатора на основе конфигурации.
     */
    public function make(Container $app): Serializer
    {
        $format = config('hive-mind.broadcast.format', 'json');

        return match ($format) {
            'msgpack' => $app->make(MsgPackSerializer::class),
            'json' => $app->make(JsonSerializer::class),
            default => $app->make(JsonSerializer::class),
        };
    }
}
