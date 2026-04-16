<?php

namespace Aleoosha\HiveMind\Exceptions;

use Exception;

class HiveOvercapacityException extends Exception
{
    public function __construct(
        protected int $health,
        string $message = "Operation blocked by HiveMind protection",
        int $code = 503,
        ?\Throwable $previous = null
    ) {
        $fullMessage = "{$message} (Current Hive Load: {$health}%)";
        parent::__construct($fullMessage, $code, $previous);
    }

    public function getHealth(): int
    {
        return $this->health;
    }
}
