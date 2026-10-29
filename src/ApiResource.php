<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\LogicException;
use Raims\HttpClient\HttpResponse;

/** Read-only response data. Use toArray() explicitly when serializing customer data. */
class ApiResource implements \ArrayAccess, \JsonSerializable
{
    protected const FIELD_TYPES = [];

    private array $values;

    public function __construct(#[\SensitiveParameter] array $data = [], private readonly ?HttpResponse $response = null)
    {
        $this->values = [];
        foreach ($data as $key => $value) {
            $type = static::FIELD_TYPES[$key] ?? null;
            if ($value !== null && is_string($type) && (is_array($value) || $value instanceof \stdClass)) {
                $value = new $type((array) $value);
            } elseif ($value !== null && is_array($type) && is_array($value) && isset($type[0])) {
                $class = $type[0];
                $value = array_map(static fn ($item) => is_array($item) || $item instanceof \stdClass ? new $class((array) $item) : $item, $value);
            } else {
                $value = self::copyValue($value);
            }
            $this->values[$key] = $value;
        }
    }

    public function __get(string $name): mixed
    {
        return self::copyValue($this->values[$name] ?? null);
    }

    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function __set(string $name, mixed $value): never
    {
        throw new LogicException('API response resources are read-only.');
    }

    public function __unset(string $name): never
    {
        throw new LogicException('API response resources are read-only.');
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? $this->__get($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new LogicException('API response resources are read-only.');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new LogicException('API response resources are read-only.');
    }

    public function getLastResponse(): ?HttpResponse
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return self::unwrap($this->values);
    }

    public function jsonSerialize(): object
    {
        return (object) self::copyValue($this->values);
    }

    private static function unwrap(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof self) {
                $values[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $values[$key] = self::unwrap($value);
            } elseif ($value instanceof \stdClass) {
                $values[$key] = self::copyValue($value);
            }
        }

        return $values;
    }

    private static function copyValue(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $copy = new \stdClass();
            foreach (get_object_vars($value) as $key => $item) {
                $copy->{$key} = self::copyValue($item);
            }

            return $copy;
        }
        if (is_array($value)) {
            return array_map(self::copyValue(...), $value);
        }

        return $value;
    }

    public function __debugInfo(): array
    {
        return ['resource' => static::class, 'fields' => array_keys($this->values), 'data' => '[redacted]', 'response' => $this->response];
    }
}
