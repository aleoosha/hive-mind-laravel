<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Factories;

use Aleoosha\Telemetry\Contracts\SerializerInterface;
use Aleoosha\HiveMind\Serializers\JsonSerializer;
use Aleoosha\HiveMind\Serializers\MsgPackSerializer;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for creating telemetry serializers based on configuration.
 */
final class SerializerFactory
{
    /**
     * Create a serializer instance based on the 'hive-mind.broadcast.format' setting.
     */
    public function make(Container $app): SerializerInterface
    {
        $format = config('hive-mind.broadcast.format', 'json');

        return match ($format) {
            'msgpack' => $app->make(MsgPackSerializer::class),
            'json'    => $app->make(JsonSerializer::class),
            default   => $app->make(JsonSerializer::class),
        };
    }
}
