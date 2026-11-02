# Raims PHP

Official PHP SDK for the Raims public API.

Install the package with Composer:

```text
composer require raims/raims-php:^0.1.0
```

The package is available on
[Packagist](https://packagist.org/packages/raims/raims-php). See the
[installation guide](docs/installation.md) for requirements, updates, and local
checkout usage.

## Requirements

64-bit PHP 8.2 or later, Composer 2, and the `curl`, `json`, and `hash` extensions.
The SDK uses cURL and requires no third-party libraries in production.

## Getting started

After installing the package in your project:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Raims\RaimsClient;

$apiKey = getenv('RAIMS_API_KEY');
if ($apiKey === false || $apiKey === '') {
    throw new RuntimeException('Set RAIMS_API_KEY on the server.');
}

$raims = new RaimsClient($apiKey);
$customers = $raims->customers->all(['page_size' => 20]);

foreach ($customers as $customer) {
    echo $customer->id, PHP_EOL;
}
```

The base URL is `https://api.raims.com` for both Sandbox and Production. The key
determines the company, environment, and permissions. Use the SDK on the server
and keep credentials out of browsers and version control.

## Create a customer and a charge

Using the `$raims` instance above, create the customer and pass the returned
`id` when creating the charge. In this example, `$customerTaxId` is the valid
CPF or CNPJ of the customer authorized by your application.
`$customerIdempotencyKey` and `$chargeIdempotencyKey` are distinct keys created
and persisted by the application before the requests to identify each intent.

`$chargeDueDate` is the due date chosen and persisted for the order, in Unix
seconds UTC. If you retry a request after losing its response, reuse the same
key and parameters, including this due date.

Amounts are integer cents; `15000` represents BRL 150.00. API dates use Unix
seconds UTC. If the tax ID already exists in the same account and environment,
the request updates that customer and preserves its identifier.

```php
$customer = $raims->customers->create([
    'tax_id' => $customerTaxId,
], [
    'idempotency_key' => $customerIdempotencyKey,
]);

$charge = $raims->charges->create([
    'customer_id' => $customer->id,
    'amount_cents' => 15000,
    'due_date' => $chargeDueDate,
    'title' => 'Order 1234',
], [
    'idempotency_key' => $chargeIdempotencyKey,
]);

$status = $charge->getLastResponse()->statusCode;
$requestId = $charge->getLastResponse()->getRequestId();
```

A `202` response requires you to follow the resource. Receiving the response
alone does not confirm payment or financial completion. The SDK does not
generate idempotency keys or retry writes automatically. Read the
[idempotency and error handling guide](docs/errors-and-retries.md).

## Services

| Property | Resources |
| --- | --- |
| `$raims->accounts` | Account details, fees, and checkout customization |
| `$raims->balances` | Balance and forecast |
| `$raims->customers` | Customer creation and retrieval |
| `$raims->charges` | Charges, payments, documents, and refunds for a charge |
| `$raims->paymentLinks` | Payment links and email delivery requests |
| `$raims->refunds` | Refunds and receipts |
| `$raims->disputes` | Disputes |
| `$raims->statements` | Statements |
| `$raims->withdrawals` | Withdrawals, limits, summary, and automatic settings |
| `$raims->withdrawalDestinations` | Withdrawal destinations |
| `$raims->webhooks` | Registration, pause, health, testing, and secret rotation |
| `$raims->webhookEventTypes` | Available event types |
| `$raims->simulations->charges` | Charge simulations with a Sandbox key |
| `$raims->simulations->refunds` | Refund simulations with a Sandbox key |

`create`, `retrieve`, `update`, and `all` follow the same pattern across services.
See the [SDK reference](docs/reference.md) for each service's parameters and
responses.

## Documentation

- [Installation and requirements](docs/installation.md)
- [Configuration, responses, files, and partial updates](docs/usage.md)
- [Pagination](docs/pagination.md)
- [Errors, idempotency, and retries](docs/errors-and-retries.md)
- [Receiving and verifying webhooks](docs/webhooks.md)
- [SDK reference](docs/reference.md)
- [Contributing](CONTRIBUTING.md) and [security](SECURITY.md)
- [API documentation](https://docs.raims.com/br/api)

## License

Distributed under the [MIT](LICENSE) license.
