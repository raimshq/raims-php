<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\InvalidArgumentException;

/** Overrides for one API request; credentials are never included in debug output. */
final class RequestOptions
{
    public readonly ?string $apiKey;
    public readonly ?string $idempotencyKey;
    public readonly ?float $timeout;
    public readonly ?int $maxNetworkRetries;

    public function __construct(#[\SensitiveParameter] array $options = [])
    {
        $unknown = array_diff(array_keys($options), ['api_key', 'idempotency_key', 'timeout', 'max_network_retries']);
        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown request option.');
        }
        $this->apiKey = self::optionalString($options, 'api_key');
        $this->idempotencyKey = self::optionalString($options, 'idempotency_key');
        if ($this->apiKey !== null) {
            self::validateApiKey($this->apiKey);
        }
        if ($this->idempotencyKey !== null && !preg_match('/^[\x21-\x7e]{1,255}$/D', $this->idempotencyKey)) {
            throw new InvalidArgumentException('Idempotency key must contain 1 to 255 visible ASCII characters.');
        }
        $this->timeout = array_key_exists('timeout', $options) ? self::validateTimeout($options['timeout']) : null;
        $this->maxNetworkRetries = array_key_exists('max_network_retries', $options)
            ? self::validateRetries($options['max_network_retries']) : null;
    }

    public static function validateApiKey(#[\SensitiveParameter] string $value): void
    {
        if ($value === '' || !preg_match('/^[\x21-\x7e]+$/D', $value)) {
            throw new InvalidArgumentException('API key must be a non-empty string without spaces or control characters.');
        }
    }

    public static function validateTimeout(mixed $value): float
    {
        if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value <= 0) {
            throw new InvalidArgumentException('Timeout must be a finite positive number of seconds.');
        }

        return (float) $value;
    }

    public static function validateRetries(mixed $value): int
    {
        if (!is_int($value) || $value < 0 || $value > 5) {
            throw new InvalidArgumentException('max_network_retries must be an integer from 0 to 5.');
        }

        return $value;
    }

    private static function optionalString(array $options, string $key): ?string
    {
        if (!array_key_exists($key, $options)) {
            return null;
        }
        if (!is_string($options[$key])) {
            throw new InvalidArgumentException('Request credentials and idempotency keys must be strings.');
        }

        return $options[$key];
    }

    public function __debugInfo(): array
    {
        return ['apiKey' => $this->apiKey === null ? null : '[redacted]', 'idempotencyKey' => $this->idempotencyKey === null ? null : '[redacted]', 'timeout' => $this->timeout, 'maxNetworkRetries' => $this->maxNetworkRetries];
    }
}
