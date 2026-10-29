<?php

declare(strict_types=1);

namespace Raims\HttpClient;

interface HttpClientInterface
{
    /**
     * @param array<string, string> $headers
     * @throws \Raims\Exception\ApiConnectionException When the response was not received.
     */
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        float $timeout,
        float $connectTimeout,
    ): HttpResponse;
}
