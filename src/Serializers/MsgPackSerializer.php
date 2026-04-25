<?php declare(strict_types=1);

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\Telemetry\Contracts\SerializerInterface;
use MessagePack\MessagePack;

/**
 * High-performance MessagePack implementation of the telemetry serializer.
 */
class MsgPackSerializer implements SerializerInterface
{
    /**
     * Packs data into a binary MessagePack string.
     */
    public function pack(mixed $data): string
    {
        return MessagePack::pack($data);
    }

    /**
     * Unpacks a binary MessagePack string back into its original structure.
     */
    public function unpack(string $data): mixed
    {
        return MessagePack::unpack($data);
    }
}
