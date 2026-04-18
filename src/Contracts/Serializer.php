<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Contracts;

interface Serializer
{
    public function pack(array $data): string;
    public function unpack(string $data): array;
}
