<?php

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\HiveMind\Contracts\Serializer;
use MessagePack\MessagePack;

class MsgPackSerializer implements Serializer
{
    public function pack(array $data): string 
    { 
        MessagePack::pack($data); 
    }

    public function unpack(string $data): array 
    {
        MessagePack::unpack($data);
    }
}
