<?php

declare(strict_types=1);

namespace Raims\Tooling;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

// The approved manifest is independent from the generated HTTP contract.
// Checking never rewrites the baseline. --write is an explicit maintainer action.
$root = dirname(__DIR__);
$manifest = $root . '/contracts/php-api.json';
$options = array_slice($argv, 1);
if (array_diff($options, ['--check', '--write']) !== [] || count($options) > 1) {
    fwrite(STDERR, "Usage: php scripts/check-compatibility.php [--check|--write]\n");
    exit(1);
}

spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'Raims\\')) {
        return;
    }
    $path = $root . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

/** Canonicalize a reflection type without depending on union ordering. */
function apiType(?ReflectionType $type): ?string
{
    if ($type === null) {
        return null;
    }
    if ($type instanceof \ReflectionNamedType) {
        $name = $type->getName();
        return $type->allowsNull() && !in_array($name, ['mixed', 'null'], true) ? $name . '|null' : $name;
    }
    $parts = array_map(apiType(...), $type->getTypes());
    sort($parts, SORT_STRING);
    $separator = $type instanceof \ReflectionIntersectionType ? '&' : '|';

    return implode($separator, array_map(static fn ($part) => str_contains($part, '&') && $separator === '|' ? '(' . $part . ')' : $part, $parts));
}

/** Values remain typed, including enum defaults and empty arrays. */
function apiValue(mixed $value): mixed
{
    if ($value instanceof \UnitEnum) {
        return ['enum' => $value::class . '::' . $value->name];
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = apiValue($item);
        }
        return ['array' => $result];
    }
    if (is_object($value) || is_resource($value)) {
        throw new \RuntimeException('Unsupported object or resource in a public API default.');
    }

    return ['type' => get_debug_type($value), 'value' => $value];
}

/** Extract a type token; descriptions and formatting are not API contracts. */
function docType(string $text): string
{
    $depth = 0;
    $quote = null;
    $result = '';
    for ($index = 0, $length = strlen($text); $index < $length; ++$index) {
        $character = $text[$index];
        if ($quote !== null) {
            $result .= $character;
            if ($character === '\\' && $index + 1 < $length) {
                $result .= $text[++$index];
            } elseif ($character === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($character === "'" || $character === '"') {
            $quote = $character;
            $result .= $character;
        } elseif (str_contains('<{[(', $character)) {
            ++$depth;
            $result .= $character;
        } elseif (str_contains('>}])', $character)) {
            --$depth;
            $result .= $character;
        } elseif (str_contains(" \t\r\n\v\f", $character)) {
            if ($depth === 0) {
                break;
            }
        } else {
            $result .= $character;
        }
    }

    return $result;
}

/** Keep only type-bearing PHPDoc tags; changing prose remains compatible. */
function apiDoc(string|false $comment): array
{
    $doc = ['properties' => [], 'params' => [], 'types' => []];
    foreach (explode("\n", $comment ?: '') as $line) {
        $line = preg_replace('/^\s*\/?\*+\/?\s?/', '', $line);
        if (preg_match('/^@property(-read|-write)?\s+(.+?)\s+\$([A-Za-z_][A-Za-z0-9_]*)(?:\s|$)/', $line, $matches)) {
            $doc['properties'][$matches[3]] = ['access' => $matches[1] ?: '', 'type' => docType($matches[2])];
        } elseif (preg_match('/^@param\s+(.+?)\s+\$([A-Za-z_][A-Za-z0-9_]*)(?:\s|$)/', $line, $matches)) {
            $doc['params'][$matches[2]] = docType($matches[1]);
        } elseif (preg_match('/^@(return|var|extends|implements|template(?:-covariant|-contravariant)?)\s+(.+)/', $line, $matches)) {
            $value = str_starts_with($matches[1], 'template') ? trim($matches[2], " \t\r\n*/") : docType($matches[2]);
            $doc['types'][$matches[1]] = preg_replace('/\s+/', ' ', $value);
        }
    }
    foreach ($doc as &$values) {
        ksort($values, SORT_STRING);
    }
    unset($values);

    return $doc;
}

function parameter(ReflectionParameter $parameter): array
{
    $value = [
        'name' => $parameter->getName(),
        'type' => apiType($parameter->getType()),
        'optional' => $parameter->isOptional(),
        'variadic' => $parameter->isVariadic(),
        'by_reference' => $parameter->isPassedByReference(),
    ];
    if ($parameter->isDefaultValueAvailable()) {
        $value['default'] = apiValue($parameter->getDefaultValue());
    }

    return $value;
}

function method(ReflectionMethod $method): array
{
    return [
        'visibility' => $method->isPublic() ? 'public' : 'protected',
        'static' => $method->isStatic(),
        'abstract' => $method->isAbstract(),
        'final' => $method->isFinal(),
        'by_reference' => $method->returnsReference(),
        'return_type' => apiType($method->getReturnType()),
        'parameters' => array_map(parameter(...), $method->getParameters()),
        'phpdoc' => apiDoc($method->getDocComment()),
    ];
}

function sdkApi(string $root): array
{
    $classes = [];
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src', \FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $relative = substr($file->getPathname(), strlen($root . '/src/'));
        $name = 'Raims\\' . str_replace(['/', '\\'], '\\', substr($relative, 0, -4));
        if ($name === 'Raims\\Generated\\Contract') {
            continue;
        }
        if (!class_exists($name) && !interface_exists($name) && !trait_exists($name)) {
            throw new \RuntimeException('PSR-4 public symbol not found: ' . $name);
        }
        $class = new ReflectionClass($name);
        if (preg_match('/@internal\b/', $class->getDocComment() ?: '')) {
            continue;
        }
        $interfaces = $class->getInterfaceNames();
        sort($interfaces, SORT_STRING);
        $parent = $class->getParentClass();
        $entry = [
            'kind' => $class->isInterface() ? 'interface' : ($class->isTrait() ? 'trait' : ($class->isEnum() ? 'enum' : 'class')),
            'parent' => $parent === false ? null : $parent->getName(),
            'interfaces' => $interfaces,
            'abstract' => $class->isAbstract(),
            'final' => $class->isFinal(),
            'readonly' => $class->isReadOnly(),
            'methods' => [], 'properties' => [], 'constants' => [],
            'phpdoc' => apiDoc($class->getDocComment()),
        ];
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $member) {
            if ($class->isInterface() || $member->getDeclaringClass()->getName() === $name) {
                $entry['methods'][$member->getName()] = method($member);
            }
        }
        // Constructors inherited from SPL are public call contracts as well.
        if (!isset($entry['methods']['__construct']) && ($constructor = $class->getConstructor()) !== null
            && ($constructor->isPublic() || $constructor->isProtected())) {
            $entry['methods']['__construct'] = method($constructor);
        }
        if ($class->getConstructor() === null && !$class->isInterface() && !$class->isTrait() && !$class->isEnum()) {
            $entry['methods']['__construct'] = [
                'visibility' => 'public', 'static' => false, 'abstract' => false, 'final' => false,
                'by_reference' => false, 'return_type' => null, 'parameters' => [], 'phpdoc' => apiDoc(false),
            ];
        }
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $member) {
            if ($member->getDeclaringClass()->getName() !== $name) {
                continue;
            }
            $entry['properties'][$member->getName()] = [
                'visibility' => $member->isPublic() ? 'public' : 'protected',
                'static' => $member->isStatic(), 'readonly' => $member->isReadOnly(),
                'type' => apiType($member->getType()), 'phpdoc' => apiDoc($member->getDocComment()),
            ];
        }
        foreach ($class->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC) as $member) {
            if ($member->getDeclaringClass()->getName() !== $name) {
                continue;
            }
            $entry['constants'][$member->getName()] = [
                'final' => $member->isFinal(),
                'declared_type' => method_exists($member, 'getType') ? apiType($member->getType()) : null,
                'value' => apiValue($member->getValue()),
            ];
        }
        foreach (['methods', 'properties', 'constants'] as $section) {
            ksort($entry[$section], SORT_STRING);
        }
        $classes[$name] = $entry;
    }
    ksort($classes, SORT_STRING);

    return ['schema_version' => 1, 'symbols' => $classes];
}

/** Split one generated shape without splitting nested types or quoted enum values. */
function shapeParts(string $text): ?array
{
    if (!str_starts_with($text, 'array{')) {
        return null;
    }
    $parts = [];
    $start = 6;
    $depth = 0;
    $quote = null;
    for ($index = 6, $length = strlen($text); $index < $length; ++$index) {
        $character = $text[$index];
        if ($quote !== null) {
            if ($character === '\\') {
                ++$index;
            } elseif ($character === $quote) {
                $quote = null;
            }
            continue;
        }
        if ($character === "'" || $character === '"') {
            $quote = $character;
        } elseif ($character === '}' && $depth === 0) {
            if ($index > $start) {
                $parts[] = substr($text, $start, $index - $start);
            }
            $fields = [];
            foreach ($parts as $part) {
                if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)(\?)?:(.+)$/D', $part, $matches)) {
                    return null;
                }
                $fields[$matches[1]] = ['optional' => ($matches[2] ?? '') === '?', 'type' => $matches[3]];
            }
            return ['fields' => $fields, 'suffix' => substr($text, $index + 1)];
        } elseif (str_contains('<{[(', $character)) {
            ++$depth;
        } elseif (str_contains('>}])', $character)) {
            --$depth;
        } elseif ($character === ',' && $depth === 0) {
            $parts[] = substr($text, $start, $index - $start);
            $start = $index + 1;
        }
    }

    return null;
}

/** Preserve existing input fields and allow additional optional generated fields. */
function compatibleInputDocType(string $before, string $after): bool
{
    if ($before === $after) {
        return true;
    }
    if (str_starts_with($before, 'list<') && str_starts_with($after, 'list<')
        && str_ends_with($before, '>') && str_ends_with($after, '>')) {
        return compatibleInputDocType(substr($before, 5, -1), substr($after, 5, -1));
    }
    $old = shapeParts($before);
    $new = shapeParts($after);
    if ($old === null || $new === null || $old['suffix'] !== $new['suffix']) {
        return false;
    }
    foreach ($old['fields'] as $field => $contract) {
        $current = $new['fields'][$field] ?? null;
        if ($current === null || $contract['optional'] && !$current['optional']
            || !compatibleInputDocType($contract['type'], $current['type'])) {
            return false;
        }
    }
    foreach (array_diff_key($new['fields'], $old['fields']) as $contract) {
        if (!$contract['optional']) {
            return false;
        }
    }

    return true;
}

function compareDoc(array $before, array $after, string $path, array &$errors): void
{
    foreach (['properties', 'params', 'types'] as $group) {
        foreach ($before[$group] ?? [] as $name => $value) {
            $current = $after[$group][$name] ?? null;
            if ($current === null || ($group === 'params'
                    ? !compatibleInputDocType($value, $current) : $current !== $value)) {
                $errors[] = $path . ': changed or removed PHPDoc type ' . $name;
            }
        }
    }
}

function compareMethod(array $before, array $after, string $path, array &$errors, bool $extensible): void
{
    foreach (['static', 'by_reference', 'return_type'] as $field) {
        if ($before[$field] !== $after[$field]) {
            $errors[] = $path . ': changed ' . $field;
        }
    }
    if ($before['visibility'] === 'public' && $after['visibility'] !== 'public'
        || !$before['abstract'] && $after['abstract'] || !$before['final'] && $after['final']) {
        $errors[] = $path . ': reduced method availability';
    }
    foreach ($before['parameters'] as $index => $parameter) {
        $current = $after['parameters'][$index] ?? null;
        if ($current === null) {
            $errors[] = $path . ': removed parameter ' . $parameter['name'];
            continue;
        }
        foreach (['name', 'type', 'variadic', 'by_reference'] as $field) {
            if ($parameter[$field] !== $current[$field]) {
                $errors[] = $path . ': changed parameter ' . $parameter['name'] . ' ' . $field;
            }
        }
        if ($parameter['optional'] && !$current['optional']) {
            $errors[] = $path . ': parameter became required: ' . $parameter['name'];
        }
        if (array_key_exists('default', $parameter) && ($current['default'] ?? null) !== $parameter['default']) {
            $errors[] = $path . ': changed parameter default: ' . $parameter['name'];
        }
    }
    foreach (array_slice($after['parameters'], count($before['parameters'])) as $parameter) {
        if (!$parameter['optional']) {
            $errors[] = $path . ': added required parameter ' . $parameter['name'];
        } elseif ($extensible) {
            $errors[] = $path . ': added parameter can break existing overrides: ' . $parameter['name'];
        }
    }
    compareDoc($before['phpdoc'], $after['phpdoc'], $path, $errors);
}

function compareApi(array $before, array $after): array
{
    $errors = [];
    if (($before['schema_version'] ?? null) !== 1 || !isset($before['symbols']) || !is_array($before['symbols'])) {
        throw new \RuntimeException('Unsupported public PHP API manifest.');
    }
    foreach ($before['symbols'] as $name => $symbol) {
        $current = $after['symbols'][$name] ?? null;
        if ($current === null) {
            $errors[] = $name . ': removed public symbol';
            continue;
        }
        if ($symbol['kind'] !== $current['kind'] || $symbol['parent'] !== $current['parent']
            || $symbol['readonly'] !== $current['readonly'] || !$symbol['abstract'] && $current['abstract']
            || !$symbol['final'] && $current['final'] || array_diff($symbol['interfaces'], $current['interfaces']) !== []) {
            $errors[] = $name . ': changed class or interface contract';
        }
        compareDoc($symbol['phpdoc'], $current['phpdoc'], $name, $errors);
        foreach ($symbol['methods'] as $member => $contract) {
            if (!isset($current['methods'][$member])) {
                $errors[] = $name . '::' . $member . ': removed method';
            } else {
                $extensible = !$symbol['final'] && !$contract['final']
                    && ($member !== '__construct' || $contract['abstract']);
                compareMethod($contract, $current['methods'][$member], $name . '::' . $member, $errors, $extensible);
            }
        }
        foreach (array_diff_key($current['methods'], $symbol['methods']) as $member => $contract) {
            if ($contract['abstract']) {
                $errors[] = $name . '::' . $member . ': added abstract or interface obligation';
            }
        }
        foreach ($symbol['properties'] as $member => $contract) {
            $property = $current['properties'][$member] ?? null;
            if ($property === null || $contract['type'] !== $property['type'] || $contract['static'] !== $property['static']
                || $contract['readonly'] !== $property['readonly'] || $contract['visibility'] === 'public' && $property['visibility'] !== 'public') {
                $errors[] = $name . '::$' . $member . ': changed or removed property';
            } else {
                compareDoc($contract['phpdoc'], $property['phpdoc'], $name . '::$' . $member, $errors);
            }
        }
        foreach ($symbol['constants'] as $member => $contract) {
            $constant = $current['constants'][$member] ?? null;
            if ($constant === null) {
                $errors[] = $name . '::' . $member . ': removed constant';
                continue;
            }
            $releaseVersion = $name === 'Raims\\RaimsClient' && $member === 'VERSION';
            if ($contract['declared_type'] !== $constant['declared_type'] || !$contract['final'] && $constant['final']
                || $releaseVersion && $contract['value']['type'] !== $constant['value']['type']
                || !$releaseVersion && $contract['value'] !== $constant['value']) {
                $errors[] = $name . '::' . $member . ': changed constant contract';
            }
        }
    }

    return $errors;
}

try {
    $current = sdkApi($root);
    if (in_array('--write', $options, true)) {
        file_put_contents($manifest, json_encode($current, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
        echo count($current['symbols']) . " public PHP symbols written to contracts/php-api.json. Review this baseline before delivery.\n";
        exit(0);
    }
    if (!is_file($manifest)) {
        throw new \RuntimeException('Public PHP API manifest missing. Create it explicitly with composer snapshot-api -- --write after reviewing the API.');
    }
    $baseline = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
    $errors = compareApi($baseline, $current);
    if ($errors !== []) {
        fwrite(STDERR, "Public PHP API compatibility failed:\n" . implode("\n", array_map(static fn ($error) => '- ' . $error, $errors)) . "\n");
        exit(1);
    }
    echo count($baseline['symbols']) . " approved PHP symbols remain compatible.\n";
} catch (\Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
