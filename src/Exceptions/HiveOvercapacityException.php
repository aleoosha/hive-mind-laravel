<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

final class HiveOvercapacityException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $health,
        private readonly int $retryAfter = 60
    ) {
        parent::__construct($message, 503);
    }

    /**
     * Автоматический рендеринг ответа Laravel.
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
            'health' => $this->health,
        ], 503, [
            'Retry-After' => $this->retryAfter,
        ]);
    }
}
