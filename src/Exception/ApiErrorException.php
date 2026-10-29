<?php

declare(strict_types=1);

namespace Raims\Exception;

use Raims\HttpClient\HttpResponse;

/** API failures expose stable codes separately from their diagnostic message. */
class ApiErrorException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        string $message,
        private readonly ?HttpResponse $response = null,
        #[\SensitiveParameter] private readonly ?array $error = null,
    ) {
        parent::__construct($message);
    }

    public function getHttpStatus(): ?int
    {
        return $this->response?->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return isset($this->error['code']) && is_string($this->error['code']) ? $this->error['code'] : null;
    }

    public function getRequestId(): ?string
    {
        return $this->response?->getRequestId();
    }

    /** @return array<string, mixed>|null */
    public function getError(): ?array
    {
        return $this->error;
    }

    public function getHttpHeaders(): array
    {
        return $this->response->headers ?? [];
    }

    /** Number of seconds until another attempt is allowed, if supplied by the API. */
    public function getRetryAfter(): ?float
    {
        $value = $this->response?->getHeader('Retry-After');
        if ($value === null) {
            return null;
        }
        if (preg_match('/^\d+(?:\.\d+)?$/D', $value)) {
            return (float) $value;
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? null : max(0.0, (float) ($timestamp - time()));
    }

    public function __debugInfo(): array
    {
        return ['httpStatus' => $this->getHttpStatus(), 'errorCode' => $this->getErrorCode(), 'requestId' => $this->getRequestId()];
    }
}
