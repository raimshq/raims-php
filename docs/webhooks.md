# Receive webhooks

Use `Raims\Webhook` to authenticate the original body before interpreting the
event. Signatures use the Raims protocol and the `webhook-id`,
`webhook-timestamp`, and `webhook-signature` headers.

```php
use Raims\Exception\SignatureVerificationException;
use Raims\Webhook;

$payload = file_get_contents('php://input');
if ($payload === false) {
    throw new RuntimeException('Could not read the request body.');
}

// These values come from the headers received by the HTTP server.
$headers = [
    'webhook-id' => $_SERVER['HTTP_WEBHOOK_ID'] ?? '',
    'webhook-timestamp' => $_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? '',
    'webhook-signature' => $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '',
];

try {
    $event = Webhook::constructEvent($payload, $headers, $webhookSecret);
} catch (SignatureVerificationException $error) {
    http_response_code(400);
    exit;
}

// Before acknowledging over HTTP, validate and durably store the event with deduplication.
// Use your application's components for this step.
```

The secret is the `secret_key` returned when creating or rotating the webhook.
Do not pass the API's Bearer key here. Store the secret in a secret store;
later registration queries do not return it. Do not log the payload or secret.

## What the helper checks

`constructEvent()` requires valid headers, a timestamp within tolerance, a
signature matching the secret, and an object-shaped JSON body. It returns
an array of received fields without validating the event's business meaning.
`verifySignature()` performs the same authentication without parsing JSON
and returns `void`.

The default tolerance is 300 seconds, both into the past and the future:

```php
$event = Webhook::constructEvent($payload, $headers, $webhookSecret, 300);
```

Keep the server clock synchronized. Zero tolerance requires the same second
as the attempt; it does not disable time protection. Negative values are
invalid. The optional `now` argument allows a clock to be injected for tests.

Header names are case-insensitive. The helper accepts strings or lists with
one value; duplicate or ambiguous signature headers are rejected. Do not
reconstruct or reserialize JSON before verification: whitespace, Unicode,
and line breaks are part of the signed bytes.

## Rotation and processing

During secret rotation, the header can contain multiple comma-separated
`v1=` signatures. The helper accepts any signature matching the configured
secret. Use `previous_secret_expires_at` to coordinate application server
updates during rotation.

Cryptographic verification does not prevent duplicate processing of a valid
delivery within tolerance. Before responding successfully:

1. Check the event's type, environment, and resource for your integration.
2. Deduplicate by the event identifier in durable storage. The body's `id`
   identifies the event; `webhook-id` identifies the delivery and does not
   replace the event ID.
3. Store the work atomically before acknowledging over HTTP. Process the
   business effect idempotently; return an HTTP failure if storage fails.

Event `created_at` fields use ISO 8601. This is not the Unix timestamp in the
attempt header and does not automatically follow the date format used by
API resources. See the
[receiving guide](https://docs.raims.com/br/integracoes/webhooks/receber-eventos)
for events and delivery conditions.
