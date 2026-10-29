<?php

declare(strict_types=1);

namespace Raims;

/** @internal Preserve exact integral JSON numbers before PHP decodes them. */
final class JsonNumberNormalizer
{
    private const MAX_EXPANSION_OVERHEAD = 4096;
    private const NUMBER_PATTERN = '/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/';

    private function __construct()
    {
    }

    public static function normalize(#[\SensitiveParameter] string $json): string
    {
        $length = strlen($json);
        $remainingExpansion = $length + self::MAX_EXPANSION_OVERHEAD;
        $offset = 0;
        $copiedUntil = 0;
        $parts = [];
        while ($offset < $length) {
            $character = $json[$offset];
            if ($character === '"') {
                ++$offset;
                while ($offset < $length) {
                    $offset += strcspn($json, '"\\', $offset);
                    if ($offset >= $length) {
                        break;
                    }
                    if ($json[$offset] === '"') {
                        ++$offset;
                        break;
                    }
                    $offset += 2;
                }
                continue;
            }
            if ($character !== '-' && ($character < '0' || $character > '9')) {
                ++$offset;
                continue;
            }
            if (preg_match(self::NUMBER_PATTERN, $json, $matches, 0, $offset) !== 1) {
                throw new \JsonException('The response contains an invalid JSON number.');
            }
            $token = $matches[0];
            $end = $offset + strlen($token);
            if ($end < $length && !str_contains(" \t\r\n,]}", $json[$end])) {
                throw new \JsonException('The response contains an invalid JSON number.');
            }
            $normalized = self::normalizeNumber($token, $remainingExpansion);
            if ($normalized !== $token) {
                $parts[] = substr($json, $copiedUntil, $offset - $copiedUntil);
                $parts[] = $normalized;
                $copiedUntil = $end;
                $remainingExpansion -= max(0, strlen($normalized) - strlen($token));
            }
            $offset = $end;
        }
        if ($parts === []) {
            return $json;
        }
        $parts[] = substr($json, $copiedUntil);

        return implode('', $parts);
    }

    private static function normalizeNumber(#[\SensitiveParameter] string $token, int $remainingExpansion): string
    {
        if (!str_contains($token, '.') && !strpbrk($token, 'eE')) {
            return $token;
        }
        preg_match('/^(-?)([0-9]+)(?:\.([0-9]+))?(?:[eE]([+-]?[0-9]+))?$/D', $token, $parts);
        $sign = $parts[1];
        $fraction = $parts[3] ?? '';
        $digits = ltrim($parts[2] . $fraction, '0');
        if ($digits === '') {
            return '0';
        }
        $exponentText = $parts[4] ?? '0';
        $exponentBound = strlen($digits) + strlen($fraction) + strlen($token) + $remainingExpansion;
        $exponent = self::boundedExponent($exponentText, $exponentBound);
        if ($exponent === null) {
            if (str_starts_with($exponentText, '-')) {
                return $token;
            }
            throw new \JsonException('An integral JSON number exceeds the safe expansion limit.');
        }
        $scale = $exponent - strlen($fraction);
        if ($scale < 0) {
            $trailingZeros = strlen($digits) - strlen(rtrim($digits, '0'));
            if (-$scale > $trailingZeros) {
                return $token;
            }

            return $sign . substr($digits, 0, strlen($digits) + $scale);
        }
        $extraLength = strlen($sign) + strlen($digits) + $scale - strlen($token);
        if ($extraLength > $remainingExpansion) {
            throw new \JsonException('An integral JSON number exceeds the safe expansion limit.');
        }

        return $sign . $digits . str_repeat('0', $scale);
    }

    private static function boundedExponent(#[\SensitiveParameter] string $text, int $bound): ?int
    {
        $digits = ltrim(ltrim($text, '+-'), '0');
        if ($digits === '') {
            return 0;
        }
        $limit = (string) $bound;
        if (strlen($digits) > strlen($limit) || strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0) {
            return null;
        }
        $value = (int) $digits;

        return str_starts_with($text, '-') ? -$value : $value;
    }
}
