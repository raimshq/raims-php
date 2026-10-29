<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\InvalidArgumentException;
use Raims\Exception\SignatureVerificationException;

/** Verifies Raims webhook signatures against the original HTTP body. */
final class Webhook
{
    private const BASE62_ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    private function __construct()
    {
    }

    /**
     * Authenticate the delivery before decoding its JSON object.
     *
     * Preserve the original body bytes. The caller must also check the event's
     * environment and durably deduplicate its ID before processing it.
     *
     * @param array<string, string|list<string>> $headers HTTP request headers.
     * @return array<string, mixed>
     * @throws SignatureVerificationException
     * @throws InvalidArgumentException When tolerance or the clock is negative.
     */
    public static function constructEvent(
        #[\SensitiveParameter] string $payload,
        array $headers,
        #[\SensitiveParameter] string $secret,
        int $tolerance = 300,
        ?int $now = null,
    ): array {
        self::verifySignature($payload, $headers, $secret, $tolerance, $now);

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            throw new SignatureVerificationException('The webhook body is not valid JSON.');
        }

        if (!is_array($event) || !str_starts_with(ltrim($payload, " \t\r\n"), '{')) {
            throw new SignatureVerificationException('The webhook body must be a JSON object.');
        }

        return $event;
    }

    /**
     * Authenticate the original body without decoding it.
     *
     * A tolerance of zero requires the timestamp to equal the current second.
     * During secret rotation, any matching v1 signature authenticates delivery.
     *
     * @param array<string, string|list<string>> $headers HTTP request headers.
     * @throws SignatureVerificationException
     * @throws InvalidArgumentException When tolerance or the clock is negative.
     */
    public static function verifySignature(
        #[\SensitiveParameter] string $payload,
        array $headers,
        #[\SensitiveParameter] string $secret,
        int $tolerance = 300,
        ?int $now = null,
    ): void {
        $now ??= time();
        if ($tolerance < 0 || $now < 0) {
            throw new InvalidArgumentException('Webhook tolerance and current time must be non-negative.');
        }

        $id = self::readHeader($headers, 'webhook-id');
        $timestamp = self::readHeader($headers, 'webhook-timestamp');
        $signature = self::readHeader($headers, 'webhook-signature');

        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $id) !== 1) {
            throw new SignatureVerificationException('The webhook delivery ID is invalid.');
        }
        if (preg_match('/\A[0-9]{1,12}\z/', $timestamp) !== 1) {
            throw new SignatureVerificationException('The webhook timestamp is invalid.');
        }

        $signedAt = (int) $timestamp;
        $difference = $signedAt > $now ? $signedAt - $now : $now - $signedAt;
        if ($difference > $tolerance) {
            throw new SignatureVerificationException('The webhook timestamp is outside the allowed tolerance.');
        }

        $key = self::decodeSecret($secret);
        $expected = hash_hmac('sha256', $id . '.' . $timestamp . '.' . $payload, $key);

        foreach (explode(',', $signature) as $candidate) {
            if (preg_match('/\Av1=([0-9a-f]{64})\z/i', trim($candidate, " \t"), $matches) === 1
                && hash_equals($expected, strtolower($matches[1]))) {
                return;
            }
        }

        throw new SignatureVerificationException('No webhook signature matches the configured secret.');
    }

    /** @param array<string, string|list<string>> $headers */
    private static function readHeader(array $headers, string $name): string
    {
        $found = false;
        $value = null;

        foreach ($headers as $headerName => $headerValue) {
            if (!is_string($headerName) || strtolower($headerName) !== $name) {
                continue;
            }
            if ($found) {
                throw new SignatureVerificationException('A webhook signing header occurs more than once.');
            }
            $found = true;
            if (is_array($headerValue) && array_is_list($headerValue) && count($headerValue) === 1) {
                $headerValue = $headerValue[0];
            }
            $value = $headerValue;
        }

        if (!is_string($value) || $value === '' || preg_match('/[\x00-\x08\x0A-\x1F\x7F]/', $value) === 1) {
            throw new SignatureVerificationException('A required webhook signing header is missing or invalid.');
        }

        return $value;
    }

    /** Decode the canonical Base62 value into the 32 signing bytes. */
    private static function decodeSecret(#[\SensitiveParameter] string $secret): string
    {
        if (preg_match('/\Awhsec_([0-9A-Za-z]{1,43})\z/', $secret, $matches) !== 1) {
            throw new SignatureVerificationException('The webhook signing secret is invalid.');
        }

        $encoded = $matches[1];
        if (strlen($encoded) > 1 && $encoded[0] === '0') {
            throw new SignatureVerificationException('The webhook signing secret is invalid.');
        }

        $bytes = array_fill(0, 32, 0);
        for ($index = 0, $length = strlen($encoded); $index < $length; ++$index) {
            $carry = (int) strpos(self::BASE62_ALPHABET, $encoded[$index]);
            for ($offset = 0; $offset < 32; ++$offset) {
                $carry += $bytes[$offset] * 62;
                $bytes[$offset] = $carry & 255;
                $carry >>= 8;
            }
            if ($carry !== 0) {
                throw new SignatureVerificationException('The webhook signing secret is invalid.');
            }
        }

        return pack('C*', ...$bytes);
    }
}
