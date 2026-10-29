<?php

declare(strict_types=1);

namespace Raims;

/** Metadata for one HTTP attempt, without request data or credentials. */
final readonly class RequestDiagnostics
{
    public function __construct(
        public string $operationId,
        public string $method,
        public string $path,
        public int $attempt,
        public float $durationMs,
        public ?int $httpStatus,
        public ?string $requestId,
        public ?int $transportErrorCode,
        public bool $willRetry = false,
        public ?float $retryDelayMs = null,
    ) {
    }
}
