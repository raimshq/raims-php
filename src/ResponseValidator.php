<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\UnexpectedResponseException;
use Raims\Generated\Contract;
use Raims\HttpClient\HttpResponse;

/** Checks the published response structure while preserving unknown fields. */
final class ResponseValidator
{
    private function __construct()
    {
    }

    public static function validate(#[\SensitiveParameter] \stdClass $value, string $class, HttpResponse $response): void
    {
        $schema = Contract::RESPONSE_SCHEMAS[$class] ?? null;
        if ($schema === null) {
            throw new UnexpectedResponseException('The response model has no generated schema.', $response);
        }
        self::validateValue($value, $schema, $response, 0);
    }

    private static function validateValue(#[\SensitiveParameter] mixed $value, array $schema, HttpResponse $response, int $depth): void
    {
        if ($depth > 128) {
            self::fail($response);
        }
        if ($value === null) {
            if ($schema['nullable'] ?? false) {
                return;
            }
            self::fail($response);
        }
        if (isset($schema['ref'])) {
            $reference = Contract::RESPONSE_SCHEMAS[$schema['ref']] ?? null;
            if ($reference === null) {
                self::fail($response);
            }
            self::validateValue($value, $reference, $response, $depth + 1);

            return;
        }
        $type = $schema['type'] ?? null;
        if ($type === 'object') {
            if (!$value instanceof \stdClass) {
                self::fail($response);
            }
            foreach ($schema['required'] ?? [] as $field) {
                if (!property_exists($value, $field)) {
                    self::fail($response);
                }
            }
            foreach ($schema['properties'] ?? [] as $field => $property) {
                if (property_exists($value, $field)) {
                    self::validateValue($value->{$field}, $property, $response, $depth + 1);
                }
            }
        } elseif ($type === 'array') {
            if (!is_array($value) || !array_is_list($value)) {
                self::fail($response);
            }
            foreach ($value as $item) {
                self::validateValue($item, $schema['items'], $response, $depth + 1);
            }
        } elseif (!match ($type) {
            'string' => is_string($value),
            'boolean' => is_bool($value),
            'integer' => is_int($value) || self::isLargeInteger($value),
            'number' => (is_int($value) || is_float($value)) && is_finite((float) $value),
            default => false,
        }) {
            self::fail($response);
        }
    }

    private static function isLargeInteger(mixed $value): bool
    {
        if (!is_string($value) || preg_match('/^-?(?:0|[1-9][0-9]*)$/D', $value) !== 1) {
            return false;
        }
        $negative = str_starts_with($value, '-');
        $digits = $negative ? substr($value, 1) : $value;
        $limit = $negative ? substr((string) PHP_INT_MIN, 1) : (string) PHP_INT_MAX;

        return strlen($digits) > strlen($limit)
            || strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0;
    }

    private static function fail(HttpResponse $response): never
    {
        throw new UnexpectedResponseException('The API returned a resource with an invalid response structure.', $response);
    }
}
