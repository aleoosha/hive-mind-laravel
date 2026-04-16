<?php

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\HiveMind\Contracts\Serializer;

class JsonSerializer implements Serializer
{
    public function pack(array $data): string 
    { 
        json_encode($data); 
    }
    public function unpack(string $data): array 
    { 
        json_decode($data, true) ?? []; 
    }
}
