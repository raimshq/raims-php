<?php

declare(strict_types=1);

// Generates the SDK surface from the pinned, publicly distributed OpenAPI.
// This command never downloads a contract or calls the API.
$root = dirname(__DIR__);
$check = in_array('--check', $argv, true);
$source = json_decode(file_get_contents($root . '/contracts/source.json'), true, 512, JSON_THROW_ON_ERROR);
$bytes = file_get_contents($root . '/contracts/openapi.json');
if (!hash_equals($source['sha256'], hash('sha256', $bytes))) {
    throw new RuntimeException('The contract does not match contracts/source.json.');
}
$spec = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
$schemas = $spec['components']['schemas'];
$referenceCatalog = json_decode(file_get_contents($root . '/scripts/reference.en.json'), true, 512, JSON_THROW_ON_ERROR);
$referenceTranslations = $referenceCatalog['translations'] ?? null;
if (($referenceCatalog['locale'] ?? null) !== 'en' || !is_array($referenceTranslations)) {
    throw new RuntimeException('The reference requires an English editorial translation catalog.');
}
foreach ($referenceTranslations as $sourceText => $translatedText) {
    if (!is_string($sourceText) || trim($sourceText) === '' || !is_string($translatedText) || trim($translatedText) === '') {
        throw new RuntimeException('Reference translations must map exact nonempty source text to English text.');
    }
}

function referenceText(string $source): string
{
    global $referenceTranslations;
    if ($source === '') {
        return '';
    }
    if (!array_key_exists($source, $referenceTranslations)) {
        throw new RuntimeException('Missing English reference translation for exact source: ' . $source);
    }

    return trim(preg_replace('/\s+/u', ' ', $referenceTranslations[$source]));
}

// Each published operation has exactly one idiomatic PHP entry point.
$services = [
    'Accounts' => [
        'retrieveFees' => 'GetAccountFees', 'retrieveNumber' => 'GetAccountNumber',
        'retrieveCheckoutSettings' => 'GetCheckoutSettings', 'updateCheckoutSettings' => 'UpdateCheckoutSettings',
        'createCheckoutLogoUpload' => 'CreateCheckoutLogoUpload', 'createCheckoutFaviconUpload' => 'CreateCheckoutFaviconUpload',
        'deleteCheckoutAssetUpload' => 'DeleteCheckoutAssetUpload',
    ],
    'Balances' => ['retrieve' => 'GetBalance', 'forecast' => 'GetBalanceForecast'],
    'Charges' => [
        'create' => 'CreateCharge', 'all' => 'ListCharges', 'retrieve' => 'GetCharge', 'update' => 'UpdateCharge',
        'revoke' => 'RevokeCharge', 'retrievePayment' => 'GetChargePayment', 'retrieveReceipt' => 'GetChargeReceipt',
        'retrieveBoleto' => 'GetChargeBoleto', 'createRefund' => 'CreateRefund', 'allRefunds' => 'ListChargeRefunds',
        'retrieveRefundEligibility' => 'GetRefundEligibility', 'statistics' => 'GetChargeStatistics',
    ],
    'Customers' => [
        'create' => 'CreateCustomer', 'all' => 'ListCustomers', 'retrieve' => 'GetCustomer', 'update' => 'UpdateCustomer',
        'delete' => 'DeleteCustomer', 'deleteBatch' => 'DeleteCustomersBatch',
    ],
    'Disputes' => ['all' => 'ListDisputes', 'retrieve' => 'GetDispute'],
    'PaymentLinks' => [
        'create' => 'CreatePaymentLink', 'all' => 'ListPaymentLinks', 'retrieve' => 'GetPaymentLink',
        'update' => 'UpdatePaymentLink', 'sendEmail' => 'SendPaymentLinkEmail',
    ],
    'Refunds' => ['all' => 'ListRefunds', 'retrieve' => 'GetRefund', 'retrieveReceipt' => 'GetRefundReceipt'],
    'Statements' => ['all' => 'ListStatements', 'retrieve' => 'GetStatement'],
    'WebhookEventTypes' => ['all' => 'ListWebhookEventTypes'],
    'Webhooks' => [
        'create' => 'CreateWebhook', 'all' => 'ListWebhooks', 'retrieve' => 'GetWebhook', 'update' => 'UpdateWebhook',
        'delete' => 'DeleteWebhook', 'retrieveHealth' => 'GetWebhookHealth', 'setPause' => 'SetWebhookPause',
        'rotateSecret' => 'RotateWebhookSecret', 'test' => 'TestWebhook',
    ],
    'WithdrawalDestinations' => ['all' => 'ListWithdrawalDestinations', 'retrieve' => 'GetWithdrawalDestination'],
    'Withdrawals' => [
        'create' => 'CreateWithdrawal', 'all' => 'ListWithdrawals', 'retrieve' => 'GetWithdrawal',
        'retrieveLimits' => 'GetWithdrawalLimits', 'retrieveSummary' => 'GetWithdrawalSummary',
        'retrieveAutomaticSettings' => 'GetAutomaticWithdrawalSettings', 'updateAutomaticSettings' => 'UpdateAutomaticWithdrawalSettings',
    ],
    'SimulationCharges' => [
        'confirmPayment' => 'ConfirmSimulationPayment', 'failPayment' => 'FailSimulationPayment',
        'configureScenario' => 'ConfigureSimulationScenario', 'resolveScenario' => 'ResolveSimulationScenario',
        'issuePayment' => 'IssueSimulationPayment', 'compensateBoleto' => 'CompensateSimulationBoleto',
        'createRefund' => 'CreateSimulationRefund',
    ],
    'SimulationRefunds' => ['complete' => 'CompleteSimulationRefund', 'fail' => 'FailSimulationRefund'],
];

function resolve(array $value): array
{
    global $spec;
    if (!isset($value['$ref'])) {
        return $value;
    }
    $resolved = $spec;
    foreach (explode('/', substr($value['$ref'], 2)) as $segment) {
        $resolved = $resolved[$segment];
    }
    return array_replace($resolved, array_diff_key($value, ['$ref' => true]));
}

function modelName(array $schema): ?string
{
    $schema = normalizeSchema($schema);

    return isset($schema['$ref']) ? basename($schema['$ref']) : null;
}

/** Normalize only compositions whose meaning is represented by this generator. */
function normalizeSchema(array $schema): array
{
    if (isset($schema['oneOf']) || isset($schema['allOf'])) {
        throw new RuntimeException('Unsupported schema composition. Add explicit generator support before updating the contract.');
    }
    if (!isset($schema['anyOf'])) {
        return $schema;
    }
    $branches = $schema['anyOf'];
    $nonNull = array_values(array_filter($branches, static fn ($branch) => ($branch['type'] ?? null) !== 'null'));
    $null = array_values(array_filter($branches, static fn ($branch) => ($branch['type'] ?? null) === 'null'));
    if (count($branches) !== 2 || count($nonNull) !== 1 || count($null) !== 1) {
        throw new RuntimeException('Unsupported anyOf schema. Only one concrete branch and null are supported.');
    }
    $outer = array_diff_key($schema, ['anyOf' => true]);
    $normalized = array_replace(normalizeSchema($nonNull[0]), $outer);
    $normalized['x-sdk-nullable'] = true;

    return $normalized;
}

function schemaIsNullable(array $schema): bool
{
    $schema = normalizeSchema($schema);

    return ($schema['x-sdk-nullable'] ?? false) || in_array('null', (array) ($schema['type'] ?? []), true);
}

function docType(array $schema, bool $shape = false, int $depth = 0): string
{
    $schema = normalizeSchema($schema);
    $nullable = schemaIsNullable($schema);
    if (isset($schema['$ref'])) {
        if (!$shape) {
            return '\\Raims\\Model\\' . modelName($schema) . ($nullable ? '|null' : '');
        }
        $schema = resolve($schema);
    }
    if (isset($schema['enum'])) {
        return implode('|', array_map(static fn ($v) => $v === null ? 'null' : var_export($v, true), $schema['enum']));
    }
    $types = (array) ($schema['type'] ?? 'mixed');
    $type = current(array_diff($types, ['null'])) ?: 'null';
    $result = match ($type) {
        'integer' => 'int', 'boolean' => 'bool', 'number' => 'float', 'string' => 'string', 'null' => 'null',
        'array' => 'list<' . docType($schema['items'] ?? [], $shape, $depth + 1) . '>',
        'object' => 'array<string, mixed>|\\stdClass', default => 'mixed',
    };
    if ($type === 'object' && isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
        $result = 'array<string, ' . docType($schema['additionalProperties'], $shape, $depth + 1) . '>|\\stdClass';
    }
    if ($shape && $type === 'object' && isset($schema['properties']) && $depth < 4) {
        $parts = [];
        foreach ($schema['properties'] as $name => $prop) {
            $optional = in_array($name, $schema['required'] ?? [], true) ? '' : '?';
            $parts[] = $name . $optional . ': ' . docType($prop, true, $depth + 1);
        }
        $result = 'array{' . implode(', ', $parts) . '}';
    }
    return $nullable && $type !== 'null' ? $result . '|null' : $result;
}

/** Project response structure without enums, ranges, patterns or unknown-field rejection. */
function responseShape(array $schema): array
{
    $schema = normalizeSchema($schema);
    $nullable = schemaIsNullable($schema);
    if (($name = modelName($schema)) !== null) {
        return ['ref' => 'Raims\\Model\\' . $name, 'nullable' => $nullable];
    }
    $types = array_values(array_diff((array) ($schema['type'] ?? 'mixed'), ['null']));
    if (count($types) > 1) {
        throw new RuntimeException('Unsupported response type union. Add explicit generator support before updating the contract.');
    }
    $type = $types[0] ?? 'null';
    if (!in_array($type, ['object', 'array', 'string', 'integer', 'number', 'boolean', 'null', 'mixed'], true)) {
        throw new RuntimeException('Unsupported response shape type.');
    }
    $shape = ['type' => $type, 'nullable' => $nullable];
    if ($type === 'object') {
        $shape['required'] = $schema['required'] ?? [];
        $shape['properties'] = [];
        foreach ($schema['properties'] ?? [] as $field => $property) {
            $shape['properties'][$field] = responseShape($property);
        }
    } elseif ($type === 'array') {
        $shape['items'] = responseShape($schema['items'] ?? []);
    }

    return $shape;
}

function constantSegment(string $value): string
{
    $ascii = strtr($value, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ç' => 'C',
    ]);
    $ascii = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $ascii);
    $segment = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $ascii), '_'));
    if ($segment === '') {
        throw new RuntimeException('A public enum value has no usable constant name.');
    }

    return $segment;
}

/** Generate names only from public string values or the contract's explicit enum labels. */
function enumConstants(array $schema, array $path, array &$constants): void
{
    $schema = normalizeSchema($schema);
    if (isset($schema['enum'])) {
        $labels = $schema['x-raims-enum-labels'] ?? null;
        if ($labels !== null && count($labels) !== count($schema['enum'])) {
            throw new RuntimeException('Public enum labels do not match their values.');
        }
        foreach ($schema['enum'] as $index => $value) {
            if ($value === null || !is_string($value) && (!is_int($value) || $labels === null)) {
                continue;
            }
            $label = is_string($value) ? $value : $labels[$index];
            $name = implode('_', array_map('constantSegment', [...$path, $label]));
            if (array_key_exists($name, $constants) && $constants[$name]['value'] !== $value) {
                throw new RuntimeException('Public enum constant name collision: ' . $name);
            }
            $constants[$name] = ['value' => $value, 'field' => implode('.', $path)];
        }
    }
    foreach ($schema['properties'] ?? [] as $field => $property) {
        enumConstants($property, [...$path, $field], $constants);
    }
    if (isset($schema['items'])) {
        enumConstants($schema['items'], $path, $constants);
    }
}

function commentText(string $text): string
{
    return str_replace(['*/', "\r", "\n"], ['* /', '', ' '], $text);
}

function phpLiteral(mixed $value, int $depth = 1): string
{
    if (!is_array($value)) {
        return var_export($value, true);
    }
    if ($value === []) {
        return '[]';
    }
    $lines = [];
    $list = array_is_list($value);
    foreach ($value as $key => $item) {
        $lines[] = str_repeat('    ', $depth + 1)
            . ($list ? '' : var_export($key, true) . ' => ')
            . phpLiteral($item, $depth + 1) . ',';
    }

    return "[\n" . implode("\n", $lines) . "\n" . str_repeat('    ', $depth) . ']';
}

$operations = [];
$raw = [];
$models = [];
foreach ($spec['paths'] as $path => $item) {
    foreach ($item as $method => $op) {
        if (!in_array($method, ['get', 'post', 'patch', 'put', 'delete'], true)) {
            continue;
        }
        $id = $op['operationId'];
        if (($op['x-raims-availability'] ?? 'production') !== 'production') {
            throw new RuntimeException('Only published endpoints may enter this SDK.');
        }
        $parameters = array_map('resolve', array_merge($item['parameters'] ?? [], $op['parameters'] ?? []));
        $responses = [];
        foreach ($op['responses'] as $status => $response) {
            if (!str_starts_with((string) $status, '2')) {
                continue;
            }
            $schema = resolve($response)['content']['application/json']['schema'] ?? [];
            $name = modelName($schema);
            if ($name !== null) {
                $models[$name] = true;
            } elseif ((string) $status !== '204') {
                throw new RuntimeException('An explicit response model is required: ' . $id);
            }
            $responses[(string) $status] = $name === null ? null : 'Raims\\Model\\' . $name;
        }
        $query = array_values(array_map(static fn ($p) => $p['name'], array_filter($parameters, static fn ($p) => $p['in'] === 'query')));
        $headers = array_filter($parameters, static fn ($p) => $p['in'] === 'header');
        foreach ($headers as $header) {
            if ($header['name'] !== 'Idempotency-Key') {
                throw new RuntimeException('Unsupported public header: ' . $header['name']);
            }
        }
        $operations[$id] = [
            'method' => strtoupper($method), 'path' => $path, 'query' => $query,
            'body' => isset($op['requestBody']), 'idempotency' => count($headers) > 0, 'responses' => $responses,
        ];
        $raw[$id] = [$op, $parameters];
    }
}
$mapped = array_merge(...array_values(array_map('array_values', $services)));
if (count($mapped) !== count(array_unique($mapped)) || count($operations) !== $source['operations'] || array_diff(array_keys($operations), $mapped) || array_diff($mapped, array_keys($operations))) {
    throw new RuntimeException('The service map must cover every published endpoint exactly once.');
}

// Include all response dependencies, preserving named nested objects.
$visit = function (array $schema) use (&$visit, &$models, $schemas): void {
    if (($name = modelName($schema)) !== null) {
        if (isset($models[$name . ':visited'])) {
            return;
        }
        $models[$name] = true;
        $models[$name . ':visited'] = true;
        $visit($schemas[$name]);
        return;
    }
    foreach ($schema as $child) {
        if (is_array($child)) {
            $visit($child);
        }
    }
};
foreach (array_keys($models) as $name) {
    $visit(['$ref' => '#/components/schemas/' . $name]);
}
$models = array_filter($models, static fn ($name) => !str_ends_with($name, ':visited'), ARRAY_FILTER_USE_KEY);
ksort($operations);
ksort($models);
$outputs = [];
$header = "<?php\n\ndeclare(strict_types=1);\n\n// Generated by scripts/generate.php. Do not edit directly.\n\n";
$responseShapes = [];
foreach (array_keys($models) as $name) {
    $responseShapes['Raims\\Model\\' . $name] = responseShape($schemas[$name]);
}
$outputs['src/Generated/Contract.php'] = $header . "namespace Raims\\Generated;\n\nfinal class Contract\n{\n    public const SHA256 = " . var_export($source['sha256'], true) . ";\n\n    public const OPERATIONS = " . phpLiteral($operations) . ";\n\n    public const RESPONSE_SCHEMAS = " . phpLiteral($responseShapes) . ";\n}\n";

$constants = [];
foreach ($schemas as $name => $schema) {
    enumConstants($schema, [$name], $constants);
}
foreach ($raw as $operationId => [$operation, $parameters]) {
    foreach ($parameters as $parameter) {
        if ($parameter['in'] === 'query') {
            enumConstants($parameter['schema'], [$operationId, 'query', $parameter['name']], $constants);
        }
    }
}
ksort($constants);
$constantDeclarations = [];
foreach ($constants as $name => $definition) {
    $constantDeclarations[] = '    public const ' . $name . ' = ' . var_export($definition['value'], true) . ';';
}
$outputs['src/EnumValues.php'] = $header . "namespace Raims;\n\n/** Named values from public contract fields. */\nfinal class EnumValues\n{\n" . implode("\n", $constantDeclarations) . "\n\n    private function __construct()\n    {\n    }\n}\n";

foreach ($models as $name => $_) {
    $schema = $schemas[$name];
    $paginated = isset($schema['properties']['list'], $schema['properties']['page']);
    $doc = [];
    $fields = [];
    foreach ($schema['properties'] ?? [] as $field => $property) {
        $type = docType($property);
        if (!in_array($field, $schema['required'] ?? [], true) && !schemaIsNullable($property)) {
            $type .= '|null';
        }
        $doc[] = ' * @property-read ' . $type . ' $' . $field . ' ' . commentText($property['description'] ?? '');
        if (($nested = modelName($property)) !== null) {
            $fields[$field] = 'Raims\\Model\\' . $nested;
        } elseif (($nested = modelName($property['items'] ?? [])) !== null) {
            $fields[$field] = ['Raims\\Model\\' . $nested];
        }
    }
    if ($paginated) {
        $item = modelName($schema['properties']['list']['items'] ?? []);
        if ($item === null) {
            throw new RuntimeException('A paginated response must name its item model: ' . $name);
        }
        array_unshift($doc, ' * @extends \\Raims\\Collection<\\Raims\\Model\\' . $item . '>', ' *');
    }
    $pageMetadata = isset($schema['properties']['has_next'], $schema['properties']['next_after'], $schema['properties']['has_previous'], $schema['properties']['previous_before']);
    $base = $paginated ? '\\Raims\\Collection' : ($pageMetadata ? '\\Raims\\Pagination' : '\\Raims\\ApiResource');
    $outputs['src/Model/' . $name . '.php'] = $header . "namespace Raims\\Model;\n\n/**\n" . implode("\n", $doc) . "\n */\nfinal class $name extends $base\n{\n    protected const FIELD_TYPES = " . phpLiteral($fields) . ";\n}\n";
}

$reference = "# PHP SDK reference\n\nThis reference is generated from the pinned public contract. Fields retain their API names.\nSee examples and usage conditions in the [HTTP reference](https://docs.raims.com/br/api).\n\n";
foreach ($services as $service => $methods) {
    $property = lcfirst($service);
    if (str_starts_with($service, 'Simulation')) {
        $property = 'simulations->' . lcfirst(substr($service, 10));
    }
    $class = $service . 'Service';
    $body = '';
    $reference .= "## `$property`\n\n";
    foreach ($methods as $method => $id) {
        [$op, $parameters] = $raw[$id];
        $descriptor = $operations[$id];
        $pathParams = array_values(array_filter($parameters, static fn ($p) => $p['in'] === 'path'));
        $paramParts = [];
        $pathMap = [];
        $doc = ['    /**', '     * ' . commentText($op['summary'] ?? $id), '     *'];
        $refParams = [];
        foreach ($pathParams as $parameter) {
            $pname = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $parameter['name']))));
            $paramParts[] = 'string $' . $pname;
            $pathMap[] = var_export($parameter['name'], true) . ' => $' . $pname;
            $doc[] = '     * @param string $' . $pname . ' ' . commentText($parameter['description'] ?? '');
        }
        $input = $op['requestBody']['content']['application/json']['schema'] ?? null;
        if ($input === null && $descriptor['query'] !== []) {
            $input = ['type' => 'object', 'properties' => [], 'required' => []];
            foreach ($parameters as $parameter) {
                if ($parameter['in'] !== 'query') {
                    continue;
                }
                $input['properties'][$parameter['name']] = array_merge($parameter['schema'], ['description' => $parameter['description'] ?? '']);
                if ($parameter['required'] ?? false) {
                    $input['required'][] = $parameter['name'];
                }
            }
        }
        if ($input !== null) {
            $requiredInput = count(resolve($input)['required'] ?? []) > 0;
            $paramParts[] = 'array $params' . ($requiredInput ? '' : ' = []');
            $doc[] = '     * @param ' . docType($input, true) . ' $params';
        }
        $paramParts[] = 'array|RequestOptions $options = []';
        $doc[] = '     * @param array{api_key?: string, idempotency_key?: string, timeout?: float, max_network_retries?: int}|RequestOptions $options';
        $returnTypes = array_unique(array_map(static fn ($name) => $name === null ? 'null' : '\\' . $name, $descriptor['responses']));
        $return = count($returnTypes) === 1 && current($returnTypes) === 'null' ? '?\\Raims\\ApiResource' : implode('|', $returnTypes);
        $doc[] = '     * @throws \\Raims\\Exception\\ApiErrorException';
        $doc[] = '     * @throws \\Raims\\Exception\\ApiConnectionException';
        $doc[] = '     */';
        $expression = '$this->requestor->request(' . var_export($id, true) . ', [' . implode(', ', $pathMap) . '], ' . ($input === null ? '[]' : '$params') . ', $options)';
        $declarations = array_map(static fn ($part) => '#[\\SensitiveParameter] ' . $part, $paramParts);
        $body .= implode("\n", $doc) . "\n    public function $method(" . implode(', ', $declarations) . "): $return\n    {\n";
        if ($return === '?\\Raims\\ApiResource') {
            $body .= '        return ' . $expression . ";\n";
        } else {
            $conditions = array_map(static fn ($class) => $class === 'null' ? '$response !== null' : '!$response instanceof ' . $class, $returnTypes);
            $body .= '        $response = ' . $expression . ";\n";
            $body .= '        if (' . implode(' && ', $conditions) . ") {\n            throw new \\Raims\\Exception\\UnexpectedResponseException('The endpoint returned an unexpected resource type.', \$response?->getLastResponse());\n        }\n\n        return \$response;\n";
        }
        $body .= "    }\n\n";
        $signature = '$raims->' . $property . '->' . $method . '(' . implode(', ', array_map(static fn ($p) => preg_replace('/ = \[\]$/', '', preg_replace('/^(string|array|array\|RequestOptions) /', '', $p)), $paramParts)) . ')';
        $reference .= "### `$method`\n\n" . referenceText($op['summary'] ?? $id) . ".\n\n```php\n$signature;\n```\n\n`{$descriptor['method']} {$descriptor['path']}` · `$id`\n\n";
        if (isset($op['description'])) {
            $reference .= referenceText($op['description']) . "\n\n";
        }
        foreach ($pathParams as $parameter) {
            $reference .= '`' . $parameter['name'] . '`: ' . (isset($parameter['description']) ? referenceText($parameter['description']) : 'Resource identifier.') . "\n\n";
        }
        if ($input !== null) {
            $resolved = resolve($input);
            $reference .= "| Field in `\$params` | Type | Required | Description |\n| --- | --- | --- | --- |\n";
            foreach ($resolved['properties'] ?? [] as $field => $prop) {
                $description = str_replace(["\r", "\n", '|'], ['', ' ', '\\|'], referenceText($prop['description'] ?? resolve($prop)['description'] ?? ''));
                $reference .= '| `' . $field . '` | `' . str_replace('|', '\\|', docType($prop, true)) . '` | ' . (in_array($field, $resolved['required'] ?? [], true) ? 'Yes' : 'No') . ' | ' . $description . " |\n";
            }
            $reference .= "\n";
        }
        $reference .= 'Returns: ' . implode(', ', array_map(static fn ($status, $name) => '`' . $status . '` ' . ($name === null ? '`null`' : '`' . basename(str_replace('\\', '/', $name)) . '`'), array_keys($descriptor['responses']), $descriptor['responses'])) . ".\n\n";
        $reference .= $descriptor['idempotency'] ? "Accepts `idempotency_key` in `\$options`.\n\n" : "Does not accept `idempotency_key`.\n\n";
    }
    $outputs['src/Service/' . $class . '.php'] = $header . "namespace Raims\\Service;\n\nuse Raims\\RequestOptions;\n\nfinal class $class extends AbstractService\n{\n" . rtrim($body) . "\n}\n";
}
$reference .= "## Public value constants\n\nUse `Raims\\EnumValues` for named contract values. Names identify the source schema or endpoint and field; `QUERY` identifies a URL filter.\nNumeric values receive names only when the contract provides public labels; other values remain documented on their fields.\nConstants do not change the values sent to the API.\n\n```php\nuse Raims\\EnumValues;\n\n\$params = ['payment_method' => EnumValues::CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX];\n```\n\n| Constant in `EnumValues` | Value | Public contract field |\n| --- | --- | --- |\n";
foreach ($constants as $name => $definition) {
    $reference .= '| `' . $name . '` | `' . str_replace('|', '\\|', var_export($definition['value'], true)) . '` | `' . $definition['field'] . "` |\n";
}
$outputs['docs/reference.md'] = rtrim($reference) . "\n";
$different = [];
foreach (['src/Model', 'src/Service', 'src/Generated'] as $directory) {
    if (!is_dir($root . '/' . $directory)) {
        continue;
    }
    foreach (new DirectoryIterator($root . '/' . $directory) as $file) {
        $path = $directory . '/' . $file->getFilename();
        if ($file->isFile() && $file->getExtension() === 'php'
            && str_contains(file_get_contents($file->getPathname()), '// Generated by scripts/generate.php.')
            && !isset($outputs[$path])) {
            throw new RuntimeException('Remove the obsolete generated file after review: ' . $path);
        }
    }
}
foreach ($outputs as $path => $contents) {
    $absolute = $root . '/' . $path;
    if (is_file($absolute) && file_get_contents($absolute) === $contents) {
        continue;
    }
    $different[] = $path;
    if (!$check) {
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0777, true);
        }
        file_put_contents($absolute, $contents);
    }
}
if ($check && $different !== []) {
    fwrite(STDERR, "Generated files differ:\n" . implode("\n", $different) . "\n");
    exit(1);
}
echo count($operations) . ' endpoints, ' . count($models) . ' response models; ' . ($check ? 'generated files match.' : count($different) . ' files updated.') . "\n";
