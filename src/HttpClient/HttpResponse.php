<?php

declare(strict_types=1);

namespace Raims\HttpClient;

/** An HTTP response. Its body can contain sensitive customer data. */
final class HttpResponse
{
    public function __construct(
        public readonly int $statusCode,
        #[\SensitiveParameter] public readonly array $headers,
        #[\SensitiveParameter] public readonly string $body,
    ) {
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return null;
    }

    public function getRequestId(): ?string
    {
        return $this->getHeader('Request-Id') ?? $this->getHeader('X-Request-Id');
    }

    public function __debugInfo(): array
    {
        return ['statusCode' => $this->statusCode, 'requestId' => $this->getRequestId(), 'body' => '[redacted]'];
    }
}
