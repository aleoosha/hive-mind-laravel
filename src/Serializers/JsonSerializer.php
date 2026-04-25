<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\Telemetry\Contracts\SerializerInterface;

/**
 * Standard JSON implementation of the telemetry serializer.
 */
class JsonSerializer implements SerializerInterface
{
    /**
     * Packs data into a JSON string.
     */
    public function pack(mixed $data): string
    {
        return (string) json_encode($data);
    }

    /**
     * Unpacks a JSON string back into an associative array.
     */
    public function unpack(string $data): mixed
    {
        return json_decode($data, true) ?? [];
    }
}
