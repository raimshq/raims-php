<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\ApiConnectionException;
use Raims\Exception\ApiErrorException;
use Raims\Exception\ApiException;
use Raims\Exception\AuthenticationException;
use Raims\Exception\ConflictException;
use Raims\Exception\InvalidRequestException;
use Raims\Exception\InvalidArgumentException;
use Raims\Exception\NotFoundException;
use Raims\Exception\PermissionException;
use Raims\Exception\RateLimitException;
use Raims\Exception\UnexpectedResponseException;
use Raims\Generated\Contract;
use Raims\HttpClient\CurlClient;
use Raims\HttpClient\HttpClientInterface;
use Raims\HttpClient\HttpResponse;

/** Sends requests only through the generated public API operation catalog. */
final class ApiRequestor
{
    private readonly string $apiBase;
    private readonly float $timeout;
    private readonly float $connectTimeout;
    private readonly int $maxNetworkRetries;
    private readonly HttpClientInterface $httpClient;
    private readonly ?\Closure $requestObserver;
    private bool $notifyingObserver = false;

    public function __construct(#[\SensitiveParameter] private readonly string $apiKey, #[\SensitiveParameter] array $config = [])
    {
        RequestOptions::validateApiKey($apiKey);
        if (array_diff(array_keys($config), ['api_base', 'timeout', 'connect_timeout', 'max_network_retries', 'http_client', 'request_observer']) !== []) {
            throw new InvalidArgumentException('Unknown client configuration option.');
        }
        $base = $config['api_base'] ?? 'https://api.raims.com';
        if (!is_string($base)) {
            throw new InvalidArgumentException('api_base must be an HTTPS origin.');
        }
        $this->apiBase = self::validateApiBase($base);
        $this->timeout = RequestOptions::validateTimeout($config['timeout'] ?? 80.0);
        $this->connectTimeout = RequestOptions::validateTimeout($config['connect_timeout'] ?? 10.0);
        $this->maxNetworkRetries = RequestOptions::validateRetries($config['max_network_retries'] ?? 0);
        $client = $config['http_client'] ?? new CurlClient();
        if (!$client instanceof HttpClientInterface) {
            throw new InvalidArgumentException('http_client must implement HttpClientInterface.');
        }
        $this->httpClient = $client;
        $observer = $config['request_observer'] ?? null;
        if ($observer !== null && !is_callable($observer)) {
            throw new InvalidArgumentException('request_observer must be callable.');
        }
        $this->requestObserver = $observer === null ? null : \Closure::fromCallable($observer);
    }

    public function request(string $operationId, #[\SensitiveParameter] array $pathParams = [], #[\SensitiveParameter] array $params = [], #[\SensitiveParameter] array|RequestOptions $options = []): ?ApiResource
    {
        $operation = Contract::OPERATIONS[$operationId] ?? null;
        if ($operation === null) {
            throw new InvalidArgumentException('Unknown public API operation.');
        }
        $options = is_array($options) ? new RequestOptions($options) : $options;
        if ($options->idempotencyKey !== null && !$operation['idempotency']) {
            throw new InvalidArgumentException('This endpoint does not accept an idempotency key.');
        }
        $path = $this->buildPath($operation['path'], $pathParams);
        $method = $operation['method'];
        $headers = ['Authorization' => 'Bearer ' . ($options->apiKey ?? $this->apiKey), 'Accept' => 'application/json', 'User-Agent' => 'RaimsPHP/' . RaimsClient::VERSION];
        if ($options->idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $options->idempotencyKey;
        }
        $body = null;
        if ($operation['body']) {
            try {
                $body = json_encode((object) $params, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException) {
                throw new InvalidArgumentException('Request parameters must be valid JSON values.');
            }
            $headers['Content-Type'] = 'application/json';
        } else {
            if (array_diff(array_keys($params), $operation['query']) !== []) {
                throw new InvalidArgumentException('This endpoint received an unknown query parameter or an unsupported request body.');
            }
            $query = $this->encodeQuery($params);
            if ($query !== '') {
                $path .= '?' . $query;
            }
        }
        $maxRetries = in_array($method, ['GET', 'HEAD'], true) ? ($options->maxNetworkRetries ?? $this->maxNetworkRetries) : 0;
        $response = $this->send($operationId, $operation['path'], $method, $this->apiBase . $path, $headers, $body, $options->timeout ?? $this->timeout, $maxRetries);
        $resource = $this->interpretResponse($operation['responses'], $response);
        if ($resource instanceof Collection) {
            $resource->setPageLoader(fn (array $nextParams) => $this->request($operationId, $pathParams, $nextParams, $options), $params);
        }

        return $resource;
    }

    private static function validateApiBase(string $base): string
    {
        $parts = parse_url($base);
        if (preg_match('/[\x00-\x20\x7f]/', $base) || $parts === false || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path'] ?? '', ['', '/'], true)) {
            throw new InvalidArgumentException('api_base must be an HTTPS origin without credentials, a path, query or fragment.');
        }
        if (strtolower($parts['scheme']) !== 'https' && !(strtolower($parts['scheme']) === 'http' && in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '[::1]'], true))) {
            throw new InvalidArgumentException('api_base requires HTTPS; HTTP is allowed only on loopback for local development.');
        }

        return rtrim($base, '/');
    }

    private function buildPath(string $template, #[\SensitiveParameter] array $params): string
    {
        preg_match_all('/\{([^}]+)\}/', $template, $matches);
        if (array_diff(array_keys($params), $matches[1]) !== []) {
            throw new InvalidArgumentException('Unexpected path parameter.');
        }
        foreach ($matches[1] as $name) {
            $value = $params[$name] ?? null;
            if (!is_string($value) || $value === '' || $value === '.' || $value === '..' || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new InvalidArgumentException('Each path parameter must be a non-empty identifier without control characters.');
            }
            $template = str_replace('{' . $name . '}', rawurlencode($value), $template);
        }

        return $template;
    }

    private function encodeQuery(#[\SensitiveParameter] array $params): string
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            $values = is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (!is_scalar($item) || is_float($item) && !is_finite($item)) {
                    throw new InvalidArgumentException('Query values must be scalar values or lists of scalar values.');
                }
                $pairs[] = rawurlencode((string) $key) . '=' . rawurlencode(is_bool($item) ? ($item ? 'true' : 'false') : (string) $item);
            }
        }

        return implode('&', $pairs);
    }

    private function send(string $operationId, string $path, string $method, #[\SensitiveParameter] string $url, #[\SensitiveParameter] array $headers, #[\SensitiveParameter] ?string $body, float $timeout, int $maxRetries): HttpResponse
    {
        for ($attempt = 0; ; ++$attempt) {
            $startedAt = hrtime(true);
            try {
                $response = $this->httpClient->request($method, $url, $headers, $body, $timeout, min($timeout, $this->connectTimeout));
            } catch (ApiConnectionException $error) {
                $durationMs = (hrtime(true) - $startedAt) / 1000000;
                $delay = $attempt < $maxRetries ? $this->retryDelay($attempt) : null;
                $this->notifyObserver(new RequestDiagnostics($operationId, $method, $path, $attempt + 1, $durationMs, null, null, $error->getTransportErrorCode(), $delay !== null, $delay === null ? null : $delay * 1000));
                if ($delay === null) {
                    throw $error;
                }
                usleep((int) ceil($delay * 1000000));
                continue;
            }
            $durationMs = (hrtime(true) - $startedAt) / 1000000;
            $eligible = $attempt < $maxRetries && in_array($response->statusCode, [408, 429, 500, 502, 503, 504], true);
            $delay = $eligible ? $this->retryDelay($attempt, $response) : null;
            $this->notifyObserver(new RequestDiagnostics($operationId, $method, $path, $attempt + 1, $durationMs, $response->statusCode, $response->getRequestId(), null, $delay !== null, $delay === null ? null : $delay * 1000));
            if ($delay !== null) {
                usleep((int) ceil($delay * 1000000));
                continue;
            }

            return $response;
        }
    }

    private function notifyObserver(RequestDiagnostics $event): void
    {
        if ($this->requestObserver === null || $this->notifyingObserver) {
            return;
        }
        $this->notifyingObserver = true;
        try {
            ($this->requestObserver)($event);
        } catch (\Throwable) {
            // Diagnostics must not change a financial result or trigger a retry.
        } finally {
            $this->notifyingObserver = false;
        }
    }

    private function retryDelay(int $attempt, ?HttpResponse $response = null): ?float
    {
        $base = min(2.0, 0.25 * (2 ** $attempt));
        $seconds = max(0.25, $base * (0.5 + random_int(0, 1000) / 2000));
        $retryAfter = $response?->getHeader('Retry-After');
        if ($retryAfter !== null) {
            $retryTime = preg_match('/^\d+(?:\.\d+)?$/D', $retryAfter) ? (float) $retryAfter : strtotime($retryAfter);
            if ($retryTime !== false) {
                $retryTime = is_int($retryTime) ? max(0, $retryTime - time()) : $retryTime;
                if ($retryTime > 30) {
                    // Return the failure rather than violate Retry-After or block for a long interval.
                    return null;
                }
                $seconds = max($seconds, $retryTime);
            }
        }
        return $seconds;
    }

    private function interpretResponse(array $successResponses, HttpResponse $response): ?ApiResource
    {
        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            if (!array_key_exists($response->statusCode, $successResponses)) {
                throw new UnexpectedResponseException('The API returned an undocumented success status.', $response);
            }
            $class = $successResponses[$response->statusCode];
            if ($class === null) {
                if (trim($response->body) !== '') {
                    throw new UnexpectedResponseException('The API returned a body for an empty response.', $response);
                }

                return null;
            }
            $data = $this->decodeObject($response);

            ResponseValidator::validate($data, $class, $response);

            return new $class((array) $data, $response);
        }
        if ($response->statusCode >= 300 && $response->statusCode < 400) {
            throw new UnexpectedResponseException('The API returned a redirect; redirects are not followed.', $response);
        }
        $error = null;
        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            if (is_array($data) && isset($data['error']) && is_array($data['error'])) {
                $error = $data['error'];
            }
        } catch (\JsonException) {
            // An edge failure may return HTML instead of the API JSON envelope.
        }
        $class = match ($response->statusCode) {
            400, 405, 413, 415, 422 => InvalidRequestException::class,
            401 => AuthenticationException::class,
            403 => PermissionException::class,
            404 => NotFoundException::class,
            409 => ConflictException::class,
            429 => RateLimitException::class,
            default => ApiException::class,
        };
        // Explicit accessors preserve API diagnostics without printing response bodies in logs.
        $code = isset($error['code']) && is_string($error['code']) && preg_match('/^[A-Z0-9_]+$/D', $error['code']) ? ' (' . $error['code'] . ')' : '';
        throw new $class('The API request failed with HTTP ' . $response->statusCode . $code . '.', $response, $error);
    }

    private function decodeObject(HttpResponse $response): \stdClass
    {
        $contentType = $response->getHeader('Content-Type');
        if ($contentType !== null && !preg_match('~^application/(?:[a-z0-9.+-]+\+)?json(?:\s*;|$)~i', $contentType)) {
            throw new UnexpectedResponseException('The API returned a non-JSON success response.', $response);
        }
        try {
            $object = json_decode(JsonNumberNormalizer::normalize($response->body), false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            throw new UnexpectedResponseException('The API returned invalid JSON.', $response);
        }
        if (!$object instanceof \stdClass) {
            throw new UnexpectedResponseException('The API returned JSON without a resource object.', $response);
        }

        return $object;
    }

    public function __debugInfo(): array
    {
        return ['apiBase' => $this->apiBase, 'apiKey' => '[redacted]', 'timeout' => $this->timeout, 'maxNetworkRetries' => $this->maxNetworkRetries];
    }
}
