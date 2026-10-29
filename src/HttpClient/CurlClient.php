<?php

declare(strict_types=1);

namespace Raims\HttpClient;

use Raims\Exception\ApiConnectionException;
use Raims\Exception\InvalidArgumentException;
use Raims\Exception\LogicException;
use Raims\RequestOptions;

/** Reuses connections while resetting all request options between calls. */
final class CurlClient implements HttpClientInterface
{
    private ?\CurlHandle $handle = null;

    /** Release the connection cache when the transport is no longer needed. */
    public function close(): void
    {
        $this->handle = null;
    }

    public function request(string $method, #[\SensitiveParameter] string $url, #[\SensitiveParameter] array $headers, #[\SensitiveParameter] ?string $body, float $timeout, float $connectTimeout): HttpResponse
    {
        if (!extension_loaded('curl')) {
            throw new LogicException('The curl PHP extension is required by CurlClient.');
        }
        $timeout = self::milliseconds($timeout);
        $connectTimeout = self::milliseconds($connectTimeout);
        $handle = $this->handle ?? curl_init();
        if ($handle === false) {
            throw new ApiConnectionException('Unable to initialize the HTTP transport.');
        }
        $this->handle = $handle;
        curl_reset($handle);
        $responseHeaders = [];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        try {
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT_MS => $connectTimeout,
                CURLOPT_TIMEOUT_MS => $timeout,
                CURLOPT_HEADERFUNCTION => static function ($unused, string $line) use (&$responseHeaders): int {
                    if (str_starts_with($line, 'HTTP/')) {
                        $responseHeaders = [];
                    } elseif (str_contains($line, ':')) {
                        [$name, $value] = explode(':', $line, 2);
                        $name = strtolower(trim($name));
                        $value = trim($value);
                        $responseHeaders[$name] = isset($responseHeaders[$name]) ? $responseHeaders[$name] . ', ' . $value : $value;
                    }

                    return strlen($line);
                },
            ];
            if (defined('CURLOPT_PROTOCOLS_STR')) {
                $options[constant('CURLOPT_PROTOCOLS_STR')] = 'http,https';
            } else {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS | CURLPROTO_HTTP;
            }
            curl_setopt_array($handle, $options);
            if ($body !== null) {
                curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
            }
            $responseBody = curl_exec($handle);
            if ($responseBody === false) {
                // Raw cURL errors may include destinations or credentials supplied by a caller.
                $errorCode = curl_errno($handle);
                throw new ApiConnectionException('The API response was not received (transport error ' . $errorCode . ').', transportErrorCode: $errorCode);
            }

            return new HttpResponse((int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $responseHeaders, $responseBody);
        } finally {
            // Keep the connection cache, but detach headers, body and callbacks now.
            curl_reset($handle);
        }
    }

    private static function milliseconds(float $seconds): int
    {
        RequestOptions::validateTimeout($seconds);
        if ($seconds >= PHP_INT_MAX / 1000) {
            throw new InvalidArgumentException('Timeout exceeds the transport limit.');
        }

        return max(1, (int) ceil($seconds * 1000));
    }
}
