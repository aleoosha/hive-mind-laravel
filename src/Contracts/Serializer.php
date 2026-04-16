<?php

namespace Aleoosha\HiveMind\Contracts;

interface Serializer
{
    public function pack(array $data): string;
    public function unpack(string $data): array;
}
