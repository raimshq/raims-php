# Client usage

## Configuration

Each instance keeps its own credential. Use separate instances when a process
serves different accounts, or pass `api_key` explicitly in a request's options.

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'timeout' => 80.0,
    'connect_timeout' => 10.0,
    'max_network_retries' => 0,
]);

$customer = $raims->customers->retrieve($customerId, [
    'timeout' => 60.0,
]);
```

| Client option | Default | Effect |
| --- | --- | --- |
| `api_key` | Required | Key sent as a Bearer token |
| `api_base` | `https://api.raims.com` | HTTPS origin without a path, query, or credentials |
| `timeout` | `80.0` | Maximum duration of each attempt, in seconds |
| `connect_timeout` | `10.0` | Connection deadline, bounded by the attempt deadline |
| `max_network_retries` | `0` | Zero to five additional retries for reads only |
| `http_client` | `CurlClient` | `HttpClientInterface` implementation |
| `request_observer` | Disabled | Callback with metadata for each HTTP attempt |

`api_base` accepts HTTP only on loopback for development. Changing the origin
does not select the key's environment. Never configure an origin you do not
control: it will receive the credential.

Per-request options are `api_key`, `idempotency_key`, `timeout`, and
`max_network_retries`. They can also be supplied through
`new RequestOptions([...])`. Arbitrary headers are not accepted. The API sets
the `Request-Id` for each attempt.

A custom transport must throw `ApiConnectionException` when it does not receive
a response so the SDK can handle the failure and retry reads. Application
exceptions are not automatically converted into network failures.

`CurlClient` reuses connections between calls on the same instance. Headers,
body, method, and callbacks are reset for every call, including when the key
changes. To control the lifetime of this cache explicitly, inject the transport
and call `close()` when its work is complete:

```php
$httpClient = new \Raims\HttpClient\CurlClient();
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'http_client' => $httpClient,
]);

try {
    $customers = $raims->customers->all();
} finally {
    $httpClient->close();
}
```

A later call through the same transport opens another connection. The
transport also closes when the process no longer references it.

## Optional diagnostics

Configure a callback to observe each attempt, including retries, connection
failures, and `204` responses:

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'request_observer' => static function (\Raims\RequestDiagnostics $event): void {
        error_log(json_encode($event, JSON_THROW_ON_ERROR));
    },
]);
```

`RequestDiagnostics` contains only `operationId`, `method`, `path`, `attempt`,
`durationMs`, `httpStatus`, `requestId`, `transportErrorCode`, `willRetry`, and
`retryDelayMs`. The path is the endpoint template, such as
`/v1/customers/{id}`; it contains neither the supplied identifier nor the query.
`attempt` starts at 1. Duration measures only the attempt and excludes the wait
between retries. Status and Request-Id are null when no response was received;
the transport code depends on the HTTP client.

`willRetry` indicates whether the SDK decided to make another automatic
attempt. When true, `retryDelayMs` is the planned wait in milliseconds;
otherwise, the delay is `null`. The callback receives this decision before
the wait. Successful and final attempts, writes, and rejections requiring more
than 30 seconds of waiting have `willRetry: false`. The field does not confirm
that another attempt ran if the process was interrupted.

This metadata describes the transport. HTTP `202` remains a pending result,
and an HTTP `200` can still fail structural validation. Group failures in your
application before sending alerts. The SDK does not send logs or telemetry on
its own. Callback exceptions are isolated to preserve the request result;
handle collector failures inside the callback. Calls made by the callback do
not recursively notify the same observer.

## Responses

Services return classes in `Raims\Model`, with properties documented for editor
support and hydrated nested objects. Resources are read-only:

```php
$charge = $raims->charges->retrieve($chargeId);
$id = $charge->id;
$sameId = $charge['id'];
$pix = $charge->payment_information->pix;
$data = $charge->toArray();
$json = json_encode($charge, JSON_THROW_ON_ERROR);

$http = $charge->getLastResponse();
$status = $http->statusCode;
$requestId = $http->getRequestId();
$replayed = $http->getHeader('Idempotency-Replayed');
```

New API fields are preserved. Classes describe the pinned contract without
converting monetary amounts to `float`, translating states, or changing
received values. The SDK requires 64-bit PHP for API integers. Equivalent JSON
representations such as `1`, `1.0`, and `1e0` are returned as integers when they
fit the runtime. This parsing preserves the precision of large integers;
values beyond PHP's capacity remain text. Fractions are rejected in integer
fields. `$http->body` retains the original response text.

The SDK checks required fields and structural types before constructing a
resource. A list without required pagination, a scalar item where an object
is expected, or a missing required field raises `UnexpectedResponseException`.
New fields and enum values are preserved. The SDK does not repeat the server's
format, limit, or business-rule validation.

`getLastResponse()` is available on the top-level resource. Nested objects do
not represent another request. `DeleteWebhook` returns `null` after a `204`
without a body. The SDK does not automatically follow `202` responses; query
the corresponding resource to confirm the result.

The raw body in `$http->body`, `toArray()`, and serialization may contain
personal data, secrets, or temporary URLs. Use them only where needed. Default
SDK object debugging hides the contents; this does not replace application
access and logging controls.

## Named public values

Use `Raims\EnumValues` to avoid repeated strings and numbers in your integration:

```php
use Raims\EnumValues;

$params = [
    'customer_id' => $customerId,
    'amount_cents' => 15000,
    'due_date' => time() + 86400,
    'payment_method' => EnumValues::CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX,
];
```

Constants are generated from public contract fields. Numeric values receive
names only when the contract provides explicit labels. See the catalog at
the end of the [reference](reference.md). The SDK does not automatically convert
received values or maintain a manual list of states.

## Omission, null, and empty maps

Inputs are PHP arrays using the contract's `snake_case` names. The SDK does not
remove `null`, `false`, zero, or empty text from the body. Partial updates follow
the endpoint's rules:

```php
// Omitting description preserves the charge description.
$raims->charges->update($chargeId, ['title' => 'Updated order']);

// When editing a charge, null clears the description.
$raims->charges->update($chargeId, ['description' => null]);

// An empty map must be a JSON object, not a JSON list.
$raims->customers->update($customerId, ['metadata' => (object) []]);
```

`metadata` has its own merge and removal rules; do not assume an empty map
clears everything. When editing links, removing a URL requires the respective
`clear_success_redirect_url` or `clear_cancel_redirect_url` field. Read the
endpoint contract before assigning meaning to `null` or empty text.

## Documents and uploads

Receipts and boletos return a `FinancialDocument` with `url` and `expires_at`.
The authenticated endpoint does not return PDF bytes:

```php
$document = $raims->charges->retrieveReceipt($chargeId);
$temporaryUrl = $document->url;
$expiresAt = $document->expires_at;
```

Download the temporary URL with a separate GET, without `Authorization`,
cookies, or an API key, and without changing its parameters. The URL grants
access until expiration; do not log it. If it expires, query the authenticated
endpoint again. The SDK does not follow or download these URLs through the
authenticated transport.

To customize checkout images:

1. Prepare the upload with `accounts->createCheckoutLogoUpload($params)` or
   `createCheckoutFaviconUpload($params)`, using the file's actual type and size.
2. Make a separate PUT to `upload_url` with the bytes and exact `Content-Type`
   and `Content-Length` values, without an API key, cookies, or redirects.
3. Pass the returned ID to `accounts->updateCheckoutSettings($params)`. A `202`
   response requires you to follow the image update until it is applied.

Accepted fields are listed in the [reference](reference.md). The SDK neither
uploads the file nor applies the image automatically. The component performing
the PUT must validate the destination, size, type, expiration, and completion.
