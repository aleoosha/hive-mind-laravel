<?php

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\HiveMind\Contracts\Serializer;
use MessagePack\MessagePack;

class MsgPackSerializer implements Serializer
{
    public function pack(array $data): string 
    { 
        return MessagePack::pack($data); 
    }

    public function unpack(string $data): array 
    {
        return MessagePack::unpack($data);
    }
}
