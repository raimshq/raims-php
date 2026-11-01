# Errors, idempotency, and retries

## Handle errors

Make decisions using the HTTP status and `error.code`. API messages are English
diagnostics and may change. Validation details identify `field`, `code`, and
`message` where applicable.

```php
use Raims\Exception\ApiConnectionException;
use Raims\Exception\ApiErrorException;
use Raims\Exception\ExceptionInterface;
use Raims\Exception\RateLimitException;

try {
    $customer = $raims->customers->retrieve($customerId);
} catch (RateLimitException $error) {
    $retryAfterSeconds = $error->getRetryAfter();
    $requestId = $error->getRequestId();
    // Reschedule the read according to the application's limits.
} catch (ApiConnectionException $error) {
    // No response was received. A write may have been processed.
} catch (ApiErrorException $error) {
    $status = $error->getHttpStatus();
    $code = $error->getErrorCode();
    $details = $error->getError()['details'] ?? [];
    // Display an appropriate message without logging personal data.
} catch (ExceptionInterface $error) {
    // Handle another SDK failure, such as invalid configuration or arguments.
}
```

| Exception | Condition |
| --- | --- |
| `InvalidRequestException` | HTTP 400, 405, 413, 415, or 422 |
| `AuthenticationException` | HTTP 401 |
| `PermissionException` | HTTP 403 |
| `NotFoundException` | HTTP 404 |
| `ConflictException` | HTTP 409 |
| `RateLimitException` | HTTP 429 |
| `ApiException` | Other HTTP failures, including 5xx |
| `UnexpectedResponseException` | Redirect, unexpected success status, invalid JSON, or incompatible response structure |
| `ApiConnectionException` | Transport failure or timeout |

Exceptions raised by the SDK's public interfaces implement
`Raims\Exception\ExceptionInterface`. Use it to catch library failures, while
keeping specific catches when each condition needs different handling. Native
PHP type errors and application exceptions retain their own types.

HTTP failures extend `ApiErrorException`. Client configuration and input errors
use `Raims\Exception\InvalidArgumentException`, which is also a PHP
`\InvalidArgumentException`. The SDK's `LogicException`,
`UnexpectedValueException`, and `RuntimeException` preserve the same
compatibility. An edge server's HTML response may have no `error.code`;
accessors return `null` for missing data.

`getHttpHeaders()` exposes received headers, including
`Raims-Rate-Limited-Reason` when present. Prefer logging only the code, status,
and `Request-Id`. Do not log the complete error, body, or arbitrary headers.
`getRequestId()` prefers `Request-Id` and accepts `X-Request-Id` as an alias.

For `ApiConnectionException`, `getTransportErrorCode()` returns the cURL code
when available, or `null` for a transport that does not provide this diagnostic.
The code distinguishes timeouts and connection or certificate failures without
parsing the message text. It does not confirm whether a write was processed.

## Idempotency

Pass `idempotency_key` in the options only for endpoints that declare it:

```php
$charge = $raims->charges->create($params, [
    'idempotency_key' => $persistedKeyForThisOrder,
]);
```

The key must contain 1 to 255 visible ASCII characters, without spaces. Generate
and persist the identity for your business operation before the call. After
losing a response, keep the same key and request while the result can be reused.
Do not reuse a key for a different intent or change parameters to bypass a
failure.

The SDK does not generate the key. When omitted, the header is not sent.
Support is defined per endpoint; `customers->delete($id)` does not accept it.
Methods reject a key for endpoints that do not declare this header.

The published policy retains successful responses and eligible 5xx failures
for 24 hours. It does not retain 4xx errors or explicitly retryable 503
rejections. A retained error response does not confirm the financial result.
Read [API idempotency](https://docs.raims.com/br/api/idempotencia) before deciding
to retry.

`IDEMPOTENCY_REQUEST_IN_PROGRESS` and `IDEMPOTENCY_RESULT_UNKNOWN` require
different actions. For the former, respect `Retry-After`. For the latter, query
and reconcile the resource; do not change the key or repeat the financial
intent.

## Retries

The default is zero retries. A `max_network_retries` value from 1 to 5 enables
additional attempts only for GET/HEAD on connection failures and HTTP 408,
429, 500, 502, 503, or 504. Published product methods use GET for these reads.

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'max_network_retries' => 2,
]);
```

The delay uses exponential backoff with jitter between 250 ms and 2 s to
spread retries across clients. A valid `Retry-After` can extend that wait to
30 s. If it requires more than 30 s, the SDK returns the failure so the
application can reschedule. Each attempt has its own timeout; total time may
include every deadline and delay.

POST, PATCH, and DELETE are never retried automatically, even with an
idempotency key or configured retries. A connection failure does not prove
that the server did not execute the request. `202` responses also do not
start polling or another attempt automatically.
