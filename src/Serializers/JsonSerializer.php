<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Serializers;

use Aleoosha\HiveMind\Contracts\Serializer;

class JsonSerializer implements Serializer
{
    public function pack(array $data): string 
    { 
        return json_encode($data); 
    }
    public function unpack(string $data): array 
    { 
        return json_decode($data, true) ?? []; 
    }
}
