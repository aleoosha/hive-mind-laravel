<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Support;

final class FixedPoint
{
    private const MULTIPLIER = 1000;

    public readonly int $value;

    public function __construct(float|int $value, bool $isRaw = false)
    {
        $this->value = $isRaw ? (int)$value : (int)round($value * self::MULTIPLIER);
    }

    public static function raw(int $value): self {
        return new self($value, true);
    }

    public static function fromFloat(float $value): self
    {
        return new self($value);
    }

    public function toFloat(): float
    {
        return $this->value / self::MULTIPLIER;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value, true);
    }

    public function subtract(self $other): self
    {
        return new self($this->value - $other->value, true);
    }

    public function multiply(self $other): self
    {
        $result = ($this->value * $other->value) / self::MULTIPLIER;
        return new self((int)round($result), true);
    }

    public function divide(self $other): self
    {
        if ($other->value === 0) {
            return new self(0, true);
        }
        $result = ($this->value * self::MULTIPLIER) / $other->value;
        return new self((int)round($result), true);
    }
}
