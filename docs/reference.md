# PHP SDK reference

This reference is generated from the pinned public contract. Fields retain their API names.
See examples and usage conditions in the [HTTP reference](https://docs.raims.com/br/api).

## `accounts`

### `retrieveFees`

Get account fees.

```php
$raims->accounts->retrieveFees($options);
```

`GET /v1/accounts/fees` · `GetAccountFees`

Check your company's current commercial terms for Pix and boleto receipts.

Returns: `200` `AccountFees`.

Does not accept `idempotency_key`.

### `retrieveNumber`

Get the account number.

```php
$raims->accounts->retrieveNumber($options);
```

`GET /v1/accounts/account_number` · `GetAccountNumber`

Retrieve your company's financial account branch and number at Raims. This request requires a Production key and an approved, enabled account.

Returns: `200` `AccountNumber`.

Does not accept `idempotency_key`.

### `retrieveCheckoutSettings`

Get checkout customization.

```php
$raims->accounts->retrieveCheckoutSettings($options);
```

`GET /v1/accounts/checkout_settings` · `GetCheckoutSettings`

Retrieve the images, colors, support email, and default payment methods for the company's checkout in the key's environment.

Returns: `200` `CheckoutSettings`.

Does not accept `idempotency_key`.

### `updateCheckoutSettings`

Update checkout customization.

```php
$raims->accounts->updateCheckoutSettings($params, $options);
```

`PATCH /v1/accounts/checkout_settings` · `UpdateCheckoutSettings`

Change checkout images, colors, and support email. Omitted fields preserve their current values. Uploading a new image may require tracking until it is applied.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `support_email` | `string\|null` | No | Support email displayed in checkout. Omission preserves the value; null or empty text removes the contact. |
| `hex_primary_color` | `string` | No | Primary checkout color in #RRGGBB format. Omission preserves the value; null or empty text is not accepted. |
| `hex_secondary_color` | `string` | No | Checkout background color in #RRGGBB format. Omission preserves the value; null or empty text is not accepted. |
| `logo_upload_id` | `string\|null` | No | ID returned when preparing the logo upload. Upload the file before associating it. Omission preserves the current image; null removes it. |
| `favicon_upload_id` | `string\|null` | No | ID returned when preparing the icon upload. Upload the file before associating it. Omission preserves the current image; null removes it. |

Returns: `200` `CheckoutSettings`, `202` `CheckoutSettings`.

Accepts `idempotency_key` in `$options`.

### `createCheckoutLogoUpload`

Prepare a logo upload.

```php
$raims->accounts->createCheckoutLogoUpload($params, $options);
```

`POST /v1/accounts/checkout_settings/logo_uploads` · `CreateCheckoutLogoUpload`

Obtain a temporary URL to upload the image. After uploading the bytes, associate the returned ID with checkout customization. Preparation does not change the current image.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `content_type` | `'image/png'\|'image/jpeg'` | Yes | File content type: image/png or image/jpeg. |
| `file_size` | `int` | Yes | File size in bytes. |
| `width` | `int` | Yes | Image width in pixels. |
| `height` | `int` | Yes | Image height in pixels. |

Returns: `201` `CheckoutAssetUpload`.

Accepts `idempotency_key` in `$options`.

### `createCheckoutFaviconUpload`

Prepare an icon upload.

```php
$raims->accounts->createCheckoutFaviconUpload($params, $options);
```

`POST /v1/accounts/checkout_settings/favicon_uploads` · `CreateCheckoutFaviconUpload`

Obtain a temporary URL to upload the image. After uploading the bytes, associate the returned ID with checkout customization. Preparation does not change the current image.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `content_type` | `'image/png'\|'image/jpeg'` | Yes | File content type: image/png or image/jpeg. |
| `file_size` | `int` | Yes | File size in bytes. |
| `width` | `int` | Yes | Icon width in pixels. Must equal its height. |
| `height` | `int` | Yes | Icon height in pixels. Must equal its width. |

Returns: `201` `CheckoutAssetUpload`.

Accepts `idempotency_key` in `$options`.

### `deleteCheckoutAssetUpload`

Discard an image upload.

```php
$raims->accounts->deleteCheckoutAssetUpload($id, $options);
```

`DELETE /v1/accounts/checkout_settings/uploads/{id}` · `DeleteCheckoutAssetUpload`

Discard a logo or icon upload that has not been associated with checkout. Expired uploads may also be discarded. To remove an image that is already applied, use the customization PATCH endpoint.

`id`: Identifier returned when preparing the image upload.

Returns: `200` `CheckoutAssetUploadDeleted`.

Accepts `idempotency_key` in `$options`.

## `balances`

### `retrieve`

Get the current balance.

```php
$raims->balances->retrieve($options);
```

`GET /v1/balances` · `GetBalance`

Retrieve the total, blocked, and available amounts for the company and environment of the API key. Values are in Brazilian real cents. The available balance accounts for holds and does not confirm authorization for a withdrawal or refund.

Returns: `200` `Balance`.

Does not accept `idempotency_key`.

### `forecast`

Retrieve expected receipts.

```php
$raims->balances->forecast($params, $options);
```

`GET /v1/balances/forecast` · `GetBalanceForecast`

Retrieve estimated pending receipts for the period and the projected total balance. These values are estimates and do not indicate funds available for withdrawal.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `period` | `'today'\|'last_7_days'\|'last_15_days'\|'last_30_days'` | No | Period of expected receipt dates, using the São Paulo calendar. Includes today and prior days according to the option. Does not query due dates or project upcoming days. |

Returns: `200` `BalanceForecast`.

Does not accept `idempotency_key`.

## `charges`

### `create`

Create a new charge.

```php
$raims->charges->create($params, $options);
```

`POST /v1/charges` · `CreateCharge`

Create a charge for an existing customer. Supply the amount in cents. Without a custom method list, payment_method 1 requests Pix and 2 requests boleto, with details in payment_information. Boleto uses the customer name, valid email, CPF/CNPJ, and address. Missing street, neighborhood, city, and state may be filled from the CEP when available; street number and complement come from the customer record. With a custom list, issuance occurs after selection in checkout. Use create_checkout true to obtain the link without requesting email. A return URL or send_email true also generates the link; only send_email true requests email delivery.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `customer_id` | `string` | Yes | ID of a customer in the company and environment of the API key. Copy the id returned when creating or retrieving the customer. Direct Pix requires a valid CPF/CNPJ. Direct boleto requires a name, valid email address, CPF/CNPJ, and address including CEP and street number; complete those fields on the customer record. |
| `amount_cents` | `int` | Yes | Charge amount in Brazilian real cents. For example, 10000 represents BRL 100.00. |
| `payment_method` | `0\|1\|2` | No | Charge payment method. Omit it or send 0 to allow selection in checkout. Without payment_method_types, 1 requests Pix and 2 requests boleto at creation, subject to enabled methods. Boleto uses the customer's address and fills missing fields from the CEP when available. With a custom list, send 0 or omit this field; issuance occurs after the checkout selection. |
| `due_date` | `int` | Yes | Due date in Unix seconds UTC, at least five minutes in the future. The latest date is today plus ten years, using the São Paulo calendar. |
| `title` | `string\|null` | No | Charge title. You may leave this field empty. |
| `description` | `string\|null` | No | Charge description. You may leave this field empty. |
| `send_email` | `bool` | No | With true, requests delivery of the charge to the customer's email address and generates checkout_url. For pending accounts, the customer must use the account's primary email address. Outside that condition, a missing or invalid email prevents delivery but still allows charge creation when there is no direct boleto issuance. Direct boleto requires a valid email even with false. The response does not confirm message delivery. |
| `create_checkout` | `bool` | No | With true, generates the link returned in checkout_url without requiring a return URL or requesting email delivery. With false, the link is still generated when send_email is true or a return URL is supplied. |
| `success_redirect_url` | `string\|null` | No | HTTPS URL on your website for returning after confirmed payment. Redirection does not prove payment; retrieve the charge to confirm it. Credentials in the address, literal IPs, localhost, and Raims domains are not accepted. When supplied, generates checkout_url even with send_email false. If omitted, empty, or null, no return destination is set. |
| `cancel_redirect_url` | `string\|null` | No | HTTPS URL on your website for the payer to return from checkout. Returning does not cancel the charge, Pix, or boleto. Credentials in the address, literal IPs, localhost, and Raims domains are not accepted. When supplied, generates checkout_url even with send_email false. If omitted, empty, or null, no return destination is set. |
| `payment_method_types` | `list<1\|2>\|null` | No | Allowed methods for the charge: 1 for Pix and 2 for boleto. Omission or null uses the inherited checkout settings. Supply a list without duplicates. A custom configuration replaces the account default; a link configuration can only restrict that list. Combine with payment_method omitted or set to 0. |
| `external_reference` | `string\|null` | No | Order or charge reference in your system. Null, empty text, or whitespace leaves it without a reference. It may be repeated and does not prevent duplicate charges. It differs from the reference on the customer record. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | No | Integration data associated with the charge, as key and text pairs. Keys are case-sensitive. Omission, null, or {} creates the charge without this data; pairs with a null value are not included. Empty text is a valid value. |

Returns: `201` `ChargeCreated`, `202` `ChargeCreated`.

Accepts `idempotency_key` in `$options`.

### `all`

List charges.

```php
$raims->charges->all($params, $options);
```

`GET /v1/charges` · `ListCharges`

List charges for the company and environment of your API key, with filters and cursor pagination. To move forward, use page.next_after as after while page.has_next is true, keeping the filters and sort order. Status changes may take a few seconds to appear in filters; wait before querying again.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Charges per page. Positive integers above the maximum use the maximum. Omitted, empty, zero, negative, or invalid values use 10. |
| `after` | `string` | No | Copy page.next_after from the previous response to move forward. Keep the same filters and sort order; do not send before as well. Leave it empty to start the list. |
| `before` | `string` | No | Copy page.previous_before from the previous response to move backward. Keep the same filters and sort order; do not send after as well. Leave it empty if you do not want to go backward. |
| `identifier` | `string` | No | Filter by the complete charge ID. If there is no match, list is empty. |
| `customer_id` | `string` | No | Filter by the customer's complete public ID returned when creating or retrieving the customer. The relationship remains unchanged if the name or email changes. It can be combined with other filters; keep the same customer_id when changing pages. A valid ID with no charges in the company and environment of the key returns an empty list. An empty or omitted value does not apply the filter. |
| `external_reference` | `string` | No | Filter by the charge's current reference in your system, matching the complete value, including case and accents. An empty or omitted value does not apply the filter. It can be combined with other filters; preserve the value when navigating by cursor. After creating or editing a charge, it may take a few seconds for search to reflect the reference. |
| `email` | `string` | No | Filter by the complete email address recorded on the charge. Editing the customer record does not change that email address. |
| `status` | `string` | No | Filter by one or more comma-separated codes, such as 0,1. 0 created, 1 awaiting payment, 2 authorized, 3 processing, 4 paid, 5 partially refunded, 6 refunded, 7 cancelled, 8 expired, 9 failed, 10 chargeback, 11 chargeback under review, 12 chargeback lost, and 13 chargeback won. Changes may take a few seconds to appear. |
| `payment_method` | `string` | No | Filter by the charge's payment method: 1 for Pix and 2 for boleto. To combine them, send 1,2. A charge with an undefined method may switch to Pix or boleto when the Pix code or boleto is generated in checkout. Selecting a method on the page alone does not change the filter. |
| `created_gte` | `int` | No | Filter charges created at or after this instant, inclusive, in Unix seconds UTC. If created_lte is supplied, this value must be less than or equal to it. |
| `created_lte` | `int` | No | Filter charges created at or before this instant, inclusive, in Unix seconds UTC. |
| `due_gte` | `int` | No | Filter charges due at or after this instant, inclusive, in Unix seconds UTC. It can be combined with due_lte and other filters; preserve the value when changing pages. |
| `due_lte` | `int` | No | Filter charges due at or before this instant, inclusive, in Unix seconds UTC. If due_gte is supplied, this value must be greater than or equal to it. It can be used alone or with other filters. |
| `paid_gte` | `int` | No | Filter charges paid at or after this instant, inclusive, in Unix seconds UTC. Uses the confirmed payment date, even if a refund occurred later. Charges without a confirmed payment are excluded. Preserve the value when changing pages. |
| `paid_lte` | `int` | No | Filter charges paid at or before this instant, inclusive, in Unix seconds UTC. If paid_gte is supplied, this value must be greater than or equal to it. It can be combined with other filters. |
| `sort_key` | `'amount_cents'\|'created_at'` | No | Sort by the amount in cents or creation date. If omitted, uses registration order; with a creation-date filter, uses created_at. Keep the same sort order when changing pages. |
| `sort_order` | `'asc'\|'desc'` | No | Set the sort direction. With desc, the default order shows the newest charges first. |

Returns: `200` `ChargeListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a single charge.

```php
$raims->charges->retrieve($id, $options);
```

`GET /v1/charges/{id}` · `GetCharge`

Retrieve the details, status, and available payment instructions of a charge in the company and environment of your API key.

`id`: Public charge identifier in Raims, returned when creating or listing charges.

Returns: `200` `Charge`.

Does not accept `idempotency_key`.

### `update`

Update an existing charge.

```php
$raims->charges->update($id, $params, $options);
```

`PATCH /v1/charges/{id}` · `UpdateCharge`

Update the title, description, external reference, and integration data of a charge in the company and environment of your API key. You may update metadata alone even after payment or refund. Changes to other fields remain subject to the charge's state; if any field cannot be changed, no change from the request is applied.

`id`: Public charge identifier in Raims, returned when creating or listing charges.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `title` | `string\|null` | No | Charge title. Omit it to keep the current title; send null or empty text to remove it. |
| `description` | `string\|null` | No | Charge description. Omit it to keep the current description; send null or empty text to remove it. |
| `external_reference` | `string\|null` | No | Order or charge reference in your system. Omit it to keep the current value; send null or empty text to remove it. It may be repeated on other charges and is independent of the customer reference. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | No | Integration data associated with the resource. Supply key and text pairs. Keys are case-sensitive. Omission or {} preserves the data; null removes all pairs, and null for a key removes only that key. Empty text is a valid value. |

Returns: `200` `Charge`.

Accepts `idempotency_key` in `$options`.

### `revoke`

Revoke a charge.

```php
$raims->charges->revoke($id, $options);
```

`DELETE /v1/charges/{id}` · `RevokeCharge`

Revoke an open charge in the company and environment of the API key, preserving its history. Cancellation is simulated in Sandbox. In Production, revocation may await confirmation.

`id`: Public charge identifier in Raims, returned when creating or listing charges.

Returns: `200` `ChargeRevoked`, `202` `ChargeRevocationPending`.

Accepts `idempotency_key` in `$options`.

### `retrievePayment`

Get payment details.

```php
$raims->charges->retrievePayment($id, $options);
```

`GET /v1/charges/{id}/payment` · `GetChargePayment`

Retrieve the amount, date, and method of a confirmed charge payment. For Pix receipts, end_to_end_id provides the banking identifier when available. The charge must be paid, partially refunded, or have a won dispute, with the payment date recorded. The request uses the company and environment of your API key.

`id`: Public charge identifier, returned when creating or listing charges.

Returns: `200` `PaymentReceipt`.

Does not accept `idempotency_key`.

### `retrieveReceipt`

Download a payment receipt.

```php
$raims->charges->retrieveReceipt($id, $options);
```

`GET /v1/charges/{id}/receipt` · `GetChargeReceipt`

Obtain a temporary URL to download the PDF receipt for a confirmed payment. The charge must be paid, partially refunded, or have a won dispute, with the payment date recorded. The receipt shows the original paid charge amount; refunds have their own receipts. The document is issued by Raims and does not replace a tax invoice or bank receipt. The request requires an API key for the same company and environment.

`id`: Public identifier of the charge whose receipt you want to download.

Returns: `200` `FinancialDocument`.

Does not accept `idempotency_key`.

### `retrieveBoleto`

Download a boleto.

```php
$raims->charges->retrieveBoleto($id, $options);
```

`GET /v1/charges/{id}/boleto` · `GetChargeBoleto`

Obtain a temporary URL to download the PDF of an issued boleto, including before payment. The request requires a key for the same company and environment and does not issue or renew the boleto. The document does not prove payment. In Sandbox, the PDF identifies the simulation and must not be used to pay.

`id`: Public identifier of the charge whose boleto you want to download.

Returns: `200` `FinancialDocument`.

Does not accept `idempotency_key`.

### `createRefund`

Request a refund.

```php
$raims->charges->createRefund($id, $params, $options);
```

`POST /v1/charges/{id}/refunds` · `CreateRefund`

Request a full or partial refund of a received Pix payment. In Production, funds return to the originating account and an accepted request still requires confirmation. In Sandbox, the simulation completes within the request without moving real money.

`id`: Identifier of the charge that received the Pix payment.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Yes | Amount requested for refund, in Brazilian real cents. To refund everything, supply the remaining amount shown by the eligibility query. |
| `description` | `string\|null` | No | Refund request description. You may leave this field empty. |

Returns: `200` `Refund`, `202` `Refund`.

Accepts `idempotency_key` in `$options`.

### `allRefunds`

List refunds for a charge.

```php
$raims->charges->allRefunds($id, $params, $options);
```

`GET /v1/charges/{id}/refunds` · `ListChargeRefunds`

List refund requests for a charge, newest first, with cursor pagination. This request does not initiate or repeat refunds.

`id`: Identifier of the charge that received the Pix payment.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Refunds per page. Values above the maximum use the maximum; omitted, empty, text, zero, or negative values use the default. |
| `after` | `string` | No | Cursor received in page.next_after to move forward. Copy the complete value and keep the same charge. Do not send it together with before. |
| `before` | `string` | No | Cursor received in page.previous_before to move backward. Copy the complete value and keep the same charge. Do not send it together with after. |
| `created_gte` | `int` | No | Filter refunds requested at or after this instant, inclusive, in Unix seconds UTC. Uses the request creation date, regardless of completion. Preserve the value when changing pages. |
| `created_lte` | `int` | No | Filter refunds requested at or before this instant, inclusive, in Unix seconds UTC. If created_gte is supplied, this value must be greater than or equal to it. |

Returns: `200` `RefundListResponse`.

Does not accept `idempotency_key`.

### `retrieveRefundEligibility`

Get refund eligibility.

```php
$raims->charges->retrieveRefundEligibility($id, $options);
```

`GET /v1/charges/{id}/refund_eligibility` · `GetRefundEligibility`

Check whether a charge accepts a refund request, the remaining amount, the maximum available to request now, and pending amounts. These conditions are checked again when requesting the refund.

`id`: Identifier of the charge that received the Pix payment.

Returns: `200` `RefundEligibility`.

Does not accept `idempotency_key`.

### `statistics`

Retrieve charge statistics.

```php
$raims->charges->statistics($params, $options);
```

`GET /v1/charges/statistics` · `GetChargeStatistics`

Retrieve charge counts, amounts, and payment rates to track receipts in your ERP or management system. The request uses the company and environment of your API key. Data may take a few seconds to reflect charge changes.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `period` | `'today'\|'last_7_days'\|'last_15_days'\|'last_30_days'` | No | Query period using the São Paulo calendar. today covers today; last_7_days, last_15_days, and last_30_days include today and prior days. Only those periods are accepted. start_at and end_at are response fields, not custom date filters. |

Returns: `200` `ChargeStatistics`.

Does not accept `idempotency_key`.

## `customers`

### `create`

Create a customer.

```php
$raims->customers->create($params, $options);
```

`POST /v1/customers` · `CreateCustomer`

Create a customer in the company and environment of your API key. If the CPF or CNPJ is already registered, update that customer and keep its identifier. When only the CEP is supplied as the address, street, neighborhood, city, and state may be filled automatically. Returns 201 on creation and 200 on update.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `full_name` | `string\|null` | No | Payer's full name. |
| `tax_id` | `string` | Yes | Valid CPF or CNPJ, with or without periods, slash, hyphen, and spaces. |
| `email` | `string\|null` | No | Payer's email address. Recording an email address does not send a message. |
| `dial_code` | `string\|null` | No | International dialing code. It may be supplied without phone_local. |
| `phone_local` | `string\|null` | No | Phone number without the dialing code, with or without punctuation. It may be supplied without dial_code. |
| `zip_code` | `string\|null` | No | Address CEP, with or without a hyphen. If street, neigh, city, uf, street_number, and complement are empty, the CEP may populate street, neighborhood, city, and state. It does not populate the street number or address complement. |
| `street` | `string\|null` | No | Address street. |
| `street_number` | `string\|null` | No | Address street number. |
| `complement` | `string\|null` | No | Address complement. |
| `neigh` | `string\|null` | No | Address neighborhood. |
| `city` | `string\|null` | No | Address city. |
| `uf` | `string\|null` | No | State abbreviation, such as SP. |
| `external_reference` | `string\|null` | No | Customer identifier in your system. For an existing customer, omit it to keep the reference, or send null or empty text to remove it. Use external_reference in the customer list to search for this value. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | No | Integration data associated with the resource. Supply key and text pairs. Keys are case-sensitive. Omission or {} preserves the data; null removes all pairs, and null for a key removes only that key. Empty text is a valid value. |

Returns: `200` `CustomerCreated`, `201` `CustomerCreated`.

Accepts `idempotency_key` in `$options`.

### `all`

List customers.

```php
$raims->customers->all($params, $options);
```

`GET /v1/customers` · `ListCustomers`

List customers in the company and environment of the key according to the supplied filters, with cursor pagination. Send parameters in the URL, without a request body.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Customers per page. Positive integers above the maximum use the maximum. Omitted, empty, zero, negative, or invalid values use 10. |
| `after` | `string` | No | Copy page.next_after from the previous response to move forward. Use the cursor unchanged, keep the same filters, and do not send before as well. |
| `before` | `string` | No | Copy page.previous_before from the previous response to go back one page. Keep the same filters and do not send after as well. |
| `identifier` | `string` | No | Customer's public identifier, with the cus_ prefix. |
| `name` | `string` | No | Search by customer name, with the newest records first. Leave it empty to ignore this filter. |
| `email` | `string` | No | Filter by the customer's complete email address. |
| `document` | `string` | No | Filter by the customer's complete CPF or CNPJ, with or without periods, slash, hyphen, and spaces. |
| `external_reference` | `string` | No | Filter by the customer's current reference in your system, matching the complete value, including case and accents. It can be combined with other filters. After creating or editing the customer, search may take a few seconds to reflect the reference. Preserve the value when navigating by cursor. |

Returns: `200` `CustomerListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a single customer.

```php
$raims->customers->retrieve($id, $options);
```

`GET /v1/customers/{id}` · `GetCustomer`

Retrieve customer registration details by public identifier in the company and environment of the API key. The request has no body.

`id`: Public customer identifier in Raims, returned when creating or listing customers.

Returns: `200` `CustomerListItem`.

Does not accept `idempotency_key`.

### `update`

Update an existing customer.

```php
$raims->customers->update($id, $params, $options);
```

`PATCH /v1/customers/{id}` · `UpdateCustomer`

Update the supplied customer registration fields and integration data. Omitted fields keep their current values. For text fields, null or empty text clears the content; metadata follows the rules given for that field. CPF/CNPJ, identifier, and creation date remain unchanged. The CEP does not fill other address fields in this request. Sending {} returns the current record.

`id`: Public customer identifier in Raims, returned when creating or listing customers.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `full_name` | `string\|null` | No | Payer's full name. Omit it to keep the current value; send null or empty text to remove it. |
| `email` | `string\|null` | No | Payer's email address. Updating the email address does not send a message. Omit it to keep the current value; send null or empty text to remove it. |
| `dial_code` | `string\|null` | No | International dialing code. It may be supplied without phone_local. Omit it to keep the current value; send null or empty text to remove it. |
| `phone_local` | `string\|null` | No | Phone number without the dialing code, with or without punctuation. It may be supplied without dial_code. Omit it to keep the current value; send null or empty text to remove it. |
| `zip_code` | `string\|null` | No | Address CEP, with or without a hyphen. Updating the CEP does not fill other address fields. Omit it to keep the current value; send null or empty text to remove it. |
| `street` | `string\|null` | No | Address street. Omit it to keep the current value; send null or empty text to remove it. |
| `street_number` | `string\|null` | No | Address street number. Omit it to keep the current value; send null or empty text to remove it. |
| `complement` | `string\|null` | No | Address complement. Omit it to keep the current value; send null or empty text to remove it. |
| `neigh` | `string\|null` | No | Address neighborhood. Omit it to keep the current value; send null or empty text to remove it. |
| `city` | `string\|null` | No | Address city. Omit it to keep the current value; send null or empty text to remove it. |
| `uf` | `string\|null` | No | State abbreviation, such as SP. Omit it to keep the current value; send null or empty text to remove it. |
| `external_reference` | `string\|null` | No | Customer identifier in your system. Omit it to keep the current reference; send null or empty text to remove it. Use external_reference in the customer list to search for this value. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | No | Integration data associated with the resource. Supply key and text pairs. Keys are case-sensitive. Omission or {} preserves the data; null removes all pairs, and null for a key removes only that key. Empty text is a valid value. |

Returns: `200` `CustomerListItem`.

Accepts `idempotency_key` in `$options`.

### `delete`

Delete a customer.

```php
$raims->customers->delete($id, $options);
```

`DELETE /v1/customers/{id}` · `DeleteCustomer`

Delete a customer by public identifier in the company and environment of the API key. Deletion is allowed only when no linked charges remain undeleted, including paid charges. It does not delete charges or refund amounts. Repeating the request for the same deleted record returns the same identifier with deleted: true. The request has no body.

`id`: Public customer identifier in Raims, returned when creating or listing customers.

Returns: `200` `CustomerDeleted`.

Does not accept `idempotency_key`.

### `deleteBatch`

Delete customers in a batch.

```php
$raims->customers->deleteBatch($params, $options);
```

`DELETE /v1/customers` · `DeleteCustomersBatch`

Delete up to 100 customers in the company and environment of your key in one request. Check each identifier in removed, blocked, or invalid. Undeleted charges prevent customer deletion, including paid charges. Repeated identifiers are processed once; already deleted customers appear in removed. Deleting customers does not delete charges or refund amounts.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `identifiers` | `list<string\|null>` | Yes | IDs of customers to delete. Repeated IDs do not cause more than one deletion. Invalid IDs and IDs of customers the key cannot access appear in invalid. |

Returns: `200` `CustomerBatchDeleted`.

Accepts `idempotency_key` in `$options`.

## `disputes`

### `all`

List disputes.

```php
$raims->disputes->all($params, $options);
```

`GET /v1/disputes` · `ListDisputes`

Retrieve disputes in the company and environment of the key, with filters and cursor pagination.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Disputes per page. Invalid values use the default; values above the maximum use the limit. |
| `after` | `string` | No | Cursor from page.next_after in the previous response. Preserve filters and sort order; do not send it together with before. |
| `before` | `string` | No | Cursor from page.previous_before to move backward. Preserve filters and sort order; do not send it together with after. |
| `stage` | `string` | No | Comma-separated case stages: open, in_analysis, refund_in_analysis, closed, cancelled, rejected, or failed. Closed means the review has ended without confirming a favorable decision or refund. |
| `charge_id` | `string` | No | Filter by the linked charge, using its complete public identifier. A valid ID without accessible cases returns an empty list. |
| `disputed_amount_cents` | `int` | No | Filter by the exact disputed amount, in Brazilian real cents. It does not represent the amount currently held. |
| `created_gte` | `int` | No | Include disputes created at or after this instant, in Unix seconds UTC. The boundary is inclusive. |
| `created_lte` | `int` | No | Include disputes created at or before this instant, in Unix seconds UTC. The boundary is inclusive and must be equal to or later than created_gte. |
| `sort_key` | `'created_at'\|'disputed_amount_cents'` | No | Field used to sort the list: creation date or disputed amount. |
| `sort_order` | `'asc'\|'desc'` | No | Ascending or descending order for the selected field. |

Returns: `200` `DisputeListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a dispute.

```php
$raims->disputes->retrieve($id, $options);
```

`GET /v1/disputes/{id}` · `GetDispute`

Retrieve the stage, reasons, and available results of a dispute in the company and environment of the key.

`id`: Complete dispute identifier returned by the list, with the dp_ or med_ prefix.

Returns: `200` `Dispute`.

Does not accept `idempotency_key`.

## `paymentLinks`

### `create`

Create or retrieve a payment link.

```php
$raims->paymentLinks->create($params, $options);
```

`POST /v1/payment_links` · `CreatePaymentLink`

Create the link for an existing charge or return the same link when it already exists. Supply charge_id. A new link starts active; reusing a paused link preserves the pause. Title, amount, due date, and customer come from the charge. This request does not create another charge, issue payments, or request email.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `charge_id` | `string` | Yes | Identifier of the existing charge. The link uses that charge's customer, amount, and due date. |
| `success_redirect_url` | `string\|null` | No | HTTPS return URL after confirmed payment. Omission, null, or empty text preserves the existing URL. |
| `cancel_redirect_url` | `string\|null` | No | HTTPS URL for returning to your website. Returning does not cancel the charge. Omission, null, or empty text preserves the existing URL. |
| `payment_method_types` | `list<1\|2>\|null` | No | Allowed methods for the link. Omission preserves the configuration; null removes the custom configuration. A list restricts checkout methods and cannot expand the methods allowed by the charge. |

Returns: `200` `PaymentLink`, `201` `PaymentLink`.

Accepts `idempotency_key` in `$options`.

### `all`

List payment links.

```php
$raims->paymentLinks->all($params, $options);
```

`GET /v1/payment_links` · `ListPaymentLinks`

List links in the company and environment of the key, in descending order of linked charges. Use charge_id to find a charge link and page.next_after to continue.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Links per page. |
| `after` | `string` | No | Cursor received in page.next_after. Keep the same charge_id filter. |
| `before` | `string` | No | Cursor received in page.previous_before. Do not send it together with after. |
| `charge_id` | `string` | No | Identifier of the charge whose link you want to retrieve. |

Returns: `200` `PaymentLinkListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a payment link.

```php
$raims->paymentLinks->retrieve($id, $options);
```

`GET /v1/payment_links/{id}` · `GetPaymentLink`

Retrieve the URL, configuration, and current availability of the link. is_active indicates whether the link is active; is_payable indicates whether checkout can receive payment now. The link must belong to the company and environment of the key. For amount, due date, customer, and financial status, use GET /v1/charges/{charge_id}.

`id`: Public link identifier, returned when creating or listing links.

Returns: `200` `PaymentLinkDetails`.

Does not accept `idempotency_key`.

### `update`

Update a payment link.

```php
$raims->paymentLinks->update($id, $params, $options);
```

`PATCH /v1/payment_links/{id}` · `UpdatePaymentLink`

Update return URLs, payment methods, and link pause state. Send is_active false to pause and true to reactivate the same URL; omission preserves the state. Pausing blocks new checkout actions and new email delivery requests without cancelling issued Pix or boleto payments or extending the due date. The charge must be open and not past due. Use clear_success_redirect_url or clear_cancel_redirect_url with true to remove the corresponding destination.

`id`: Public link identifier, returned when creating or listing links.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `is_active` | `bool` | No | Send false to pause checkout and true to reactivate the same URL. Omission preserves the current state. Pausing does not cancel an issued Pix or boleto or extend the due date. |
| `success_redirect_url` | `string\|null` | No | HTTPS return URL after confirmed payment. Omission, null, or empty text preserves the existing URL. To remove it, set clear_success_redirect_url to true without supplying a nonempty URL. |
| `cancel_redirect_url` | `string\|null` | No | HTTPS URL for returning to your website. Returning does not cancel the charge. Omission, null, or empty text preserves the existing URL. To remove it, set clear_cancel_redirect_url to true without supplying a nonempty URL. |
| `clear_success_redirect_url` | `bool` | No | Send true to remove the return destination after payment. False or omission does not request removal. Do not send true with a nonempty success_redirect_url. |
| `clear_cancel_redirect_url` | `bool` | No | Send true to remove the return destination for your website. False or omission does not request removal. Do not send true with a nonempty cancel_redirect_url. |
| `payment_method_types` | `list<1\|2>\|null` | No | Allowed methods for the link. Omission preserves the configuration; null removes the custom configuration. A list restricts checkout methods and cannot expand the methods allowed by the charge. |

Returns: `200` `PaymentLink`.

Accepts `idempotency_key` in `$options`.

### `sendEmail`

Send a link by email.

```php
$raims->paymentLinks->sendEmail($id, $options);
```

`POST /v1/payment_links/{id}/send_email` · `SendPaymentLinkEmail`

Request delivery or redelivery of the link to the charge recipient without creating another charge or changing checkout. The charge must be open and not past due. The minimum 60-second interval between accepted requests is shared with the Dashboard resend action. The response confirms the delivery request, not email delivery. Sandbox can also send real emails. The link must be active; pausing prevents delivery requests.

`id`: Public identifier of the link to send, returned when creating or listing links.

Returns: `202` `PaymentLinkEmailRequestAccepted`.

Accepts `idempotency_key` in `$options`.

## `refunds`

### `all`

List refunds.

```php
$raims->refunds->all($params, $options);
```

`GET /v1/refunds` · `ListRefunds`

List refund requests for charges in the company and environment of the key, newest first. Use pagination to reconcile refunds without querying each charge. This request does not initiate or repeat refunds.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Refunds per page. Values above the maximum use the maximum; omitted, empty, text, zero, or negative values use the default. |
| `after` | `string` | No | Cursor received in page.next_after to move forward in this list. Copy the complete value. Do not use a charge-specific list cursor or send it together with before. |
| `before` | `string` | No | Cursor received in page.previous_before to move backward in this list. Copy the complete value. Do not use a charge-specific list cursor or send it together with after. |
| `created_gte` | `int` | No | Filter refunds requested at or after this instant, inclusive, in Unix seconds UTC. Uses the request creation date, regardless of completion. Preserve the value when changing pages. |
| `created_lte` | `int` | No | Filter refunds requested at or before this instant, inclusive, in Unix seconds UTC. If created_gte is supplied, this value must be greater than or equal to it. |

Returns: `200` `RefundListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a refund.

```php
$raims->refunds->retrieve($id, $options);
```

`GET /v1/refunds/{id}` · `GetRefund`

Retrieve the status, amount, dates, and available reason for a confirmed refund failure. This request does not initiate or repeat the refund.

`id`: Refund identifier returned in the request or refund history.

Returns: `200` `Refund`.

Does not accept `idempotency_key`.

### `retrieveReceipt`

Download a refund receipt.

```php
$raims->refunds->retrieveReceipt($id, $options);
```

`GET /v1/refunds/{id}/receipt` · `GetRefundReceipt`

Obtain a temporary URL to download the PDF receipt for a completed refund. The document shows the ID, amount, and completion date of that refund. For charges with multiple refunds, query each desired ID. The request must be in completed with a recorded completion date. The request requires an API key for the same company and environment. The document is issued by Raims and does not replace a tax invoice or bank receipt.

`id`: Public refund identifier, returned when requesting or listing refunds.

Returns: `200` `FinancialDocument`.

Does not accept `idempotency_key`.

## `statements`

### `all`

Retrieve statements.

```php
$raims->statements->all($params, $options);
```

`GET /v1/statements` · `ListStatements`

Retrieve statement entries for the company and environment of your API key. Filter by period, entry type, or reference and navigate the results with the returned cursors. Positive amounts represent inflows or releases; negative amounts represent outflows, fees, or holds. Financial confirmation may take a short time to appear in the statement.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Entries per page. |
| `after` | `string` | No | Cursor received in page.next_after to move forward. Keep the same filters and sort order. Use only after or before. |
| `before` | `string` | No | Cursor received in page.previous_before to move backward. Keep the same filters and sort order. Use only after or before. |
| `statement_type` | `string` | No | Comma-separated entry types: 1 receipt of funds; 2 fee; 3 service fee; 4 withdrawal; 5 refund; 6 partial refund; 7 adjustment credit; 8 adjustment debit; 9 hold; 10 release. |
| `reference_id` | `string` | No | Identifier of the charge, dispute, withdrawal, or adjustment associated with the entry. Use the complete ID returned by Raims. |
| `created_gte` | `int` | No | Inclusive start of the period, in Unix seconds UTC. The filter uses the entry timestamp. |
| `created_lte` | `int` | No | Inclusive end of the period, in Unix seconds UTC. The supplied instant is the exact boundary; fractions later than the supplied second are outside the period. |
| `sort_order` | `'asc'\|'desc'` | No | Entry order by date: desc shows the newest first; asc shows the oldest first. |

Returns: `200` `StatementListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a statement entry.

```php
$raims->statements->retrieve($id, $options);
```

`GET /v1/statements/{id}` · `GetStatement`

Retrieve a statement entry by ID, with its amounts and available relationships, in the company and environment of the API key.

`id`: Entry identifier returned when retrieving a statement.

Returns: `200` `Statement`.

Does not accept `idempotency_key`.

## `webhookEventTypes`

### `all`

List available events.

```php
$raims->webhookEventTypes->all($options);
```

`GET /v1/webhook_event_types` · `ListWebhookEventTypes`

Retrieve the codes that may be selected when creating or updating a webhook. The technical webhook.test event is requested through the test endpoint and is not part of the registration event list.

Returns: `200` `WebhookEventTypeListResponse`.

Does not accept `idempotency_key`.

## `webhooks`

### `create`

Create a webhook.

```php
$raims->webhooks->create($params, $options);
```

`POST /v1/webhooks` · `CreateWebhook`

Register the HTTPS address that will receive selected events for the company and environment of your API key. Store the id to manage the destination and the secret_key to validate signatures. Registration queries do not return the secret.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `name` | `string` | Yes | Name identifying the destination. |
| `url` | `string` | Yes | HTTPS URL receiving events. Use the default port 443 and a public address outside Raims. |
| `events` | `list<'charge.created'\|'charge.paid'\|'charge.canceled'\|'charge.failed'\|'refund.requested'\|'refund.completed'\|'refund.failed'>` | Yes | Event codes this destination should receive. Consult the available event catalog. |
| `is_active` | `bool` | No | Set whether the destination can receive events after registration. |

Returns: `201` `WebhookCreated`.

Accepts `idempotency_key` in `$options`.

### `all`

List webhooks.

```php
$raims->webhooks->all($params, $options);
```

`GET /v1/webhooks` · `ListWebhooks`

Retrieve destinations registered in the company and environment of your API key, including disabled destinations. To move forward, copy page.next_after to after. The list does not return signing secrets.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Records per page. The response reports the effective size in page.page_size. |
| `after` | `string` | No | To move forward, copy page.next_after from the previous response. Use only after or before. |
| `before` | `string` | No | To move backward, copy page.previous_before from the previous response. Use only after or before. |

Returns: `200` `WebhookListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a webhook.

```php
$raims->webhooks->retrieve($webhookId, $options);
```

`GET /v1/webhooks/{webhook_id}` · `GetWebhook`

Retrieve the URL, subscribed events, and state of a webhook. The signing secret is not returned by this request.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

Returns: `200` `Webhook`.

Does not accept `idempotency_key`.

### `update`

Update a webhook.

```php
$raims->webhooks->update($webhookId, $params, $options);
```

`PATCH /v1/webhooks/{webhook_id}` · `UpdateWebhook`

Change a webhook name, URL, event selection, or activation. Send only fields you want to change; omission preserves a field value. The supplied event selection replaces the previous selection. Fields do not accept null or empty values.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `name` | `string` | No | New webhook name. |
| `url` | `string` | No | New HTTPS URL receiving events, on the default port 443. |
| `events` | `list<string>` | No | Complete list of events the destination should receive. Replaces the previous selection. |
| `is_active` | `bool` | No | Use true to allow deliveries or false to disable the destination. |

Returns: `200` `Webhook`.

Accepts `idempotency_key` in `$options`.

### `delete`

Delete a webhook.

```php
$raims->webhooks->delete($webhookId, $options);
```

`DELETE /v1/webhooks/{webhook_id}` · `DeleteWebhook`

Delete a destination that will no longer be used. Deletion prevents new deliveries, but a request that is already authorized or in progress may finish. Available history remains queryable, including by the deleted webhook_id.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

Returns: `204` `null`.

Accepts `idempotency_key` in `$options`.

### `retrieveHealth`

Get webhook failures.

```php
$raims->webhooks->retrieveHealth($webhookId, $options);
```

`GET /v1/webhooks/{webhook_id}/health` · `GetWebhookHealth`

Retrieve the known state of the destination and how long its deliveries have been failing. Data reflects available delivery results and may take time to appear after an attempt. This request does not call the destination. A healthy state indicates a known success; it does not guarantee that the address remains available. An unknown state indicates insufficient data to determine the state.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

Returns: `200` `WebhookHealth`.

Does not accept `idempotency_key`.

### `setPause`

Pause or resume deliveries.

```php
$raims->webhooks->setPause($webhookId, $params, $options);
```

`PATCH /v1/webhooks/{webhook_id}/pause` · `SetWebhookPause`

Pause deliveries for an active webhook without disabling it. New events and pending deliveries wait for resumption while within their original lifetime. Pausing does not extend that lifetime: 72 hours in Production and 4 hours in Sandbox. Send is_paused false to resume deliveries that are still valid. Resumption respects delivery limits without guaranteeing order or timing. Explicit delivery of a test event remains available while paused.

`webhook_id`: Identifier of the webhook whose deliveries will be paused or resumed.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `is_paused` | `bool` | Yes | Send true to pause deliveries or false to resume them. Repeating the same value preserves the state and pause timestamp. |

Returns: `200` `WebhookPause`.

Accepts `idempotency_key` in `$options`.

### `rotateSecret`

Rotate the signing secret.

```php
$raims->webhooks->rotateSecret($webhookId, $params, $options);
```

`POST /v1/webhooks/{webhook_id}/rotate_secret` · `RotateWebhookSecret`

Replace the secret used to sign events, preserving the webhook identifier and configuration. Store the returned secret_key and update the receiver during the transition period. Repeating a rotation with a positive period while a transition is active returns the same secret and expiration. To revoke previous secrets, send grace_period_seconds equal to zero: a new signature is generated, including during an active transition. Requests that are already authorized or in progress may arrive with the previous signature.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `grace_period_seconds` | `int` | No | Period during which the previous secret can continue signing deliveries. Zero generates a new signature and ends the validity of previous secrets, including during an active transition. Requests already authorized or in progress may arrive with the previous signature. |

Returns: `200` `WebhookSecretRotation`.

Accepts `idempotency_key` in `$options`.

### `test`

Send a test event.

```php
$raims->webhooks->test($webhookId, $options);
```

`POST /v1/webhooks/{webhook_id}/test` · `TestWebhook`

Send webhook.test to check receipt and signature validation for an active webhook. The request has no body. The test makes one HTTP attempt within five minutes and does not change charges, balances, or the destination failure count. Acceptance of the request does not confirm delivery. Check receipt in the receiver logs.

`webhook_id`: Webhook identifier, returned when registering or listing webhooks.

Returns: `202` `WebhookTestAccepted`.

Accepts `idempotency_key` in `$options`.

## `withdrawalDestinations`

### `all`

List withdrawal destinations.

```php
$raims->withdrawalDestinations->all($params, $options);
```

`GET /v1/withdrawal_destinations` · `ListWithdrawalDestinations`

Retrieve verified Pix destinations owned by your company and identify the default before requesting a withdrawal. This request requires a Production key.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Records considered for the page. The list may contain fewer destinations and still indicate a next page; continue while page.has_next is true. |
| `after` | `string` | No | Cursor received in page.next_after to move forward. Copy the entire value and keep the same API key. Do not send it together with before. |
| `before` | `string` | No | Cursor received in page.previous_before to move backward. Copy the entire value and keep the same API key. Do not send it together with after. |

Returns: `200` `WithdrawalDestinationListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a withdrawal destination.

```php
$raims->withdrawalDestinations->retrieve($id, $options);
```

`GET /v1/withdrawal_destinations/{id}` · `GetWithdrawalDestination`

Retrieve a Pix destination owned by your company using the identifier returned in the list. The response shows the masked key and indicates whether it is the current default. Use a Production key.

`id`: Destination identifier returned by the list. Use the complete ID with the wdst_ prefix; a wd_ withdrawal ID belongs to a different resource.

Returns: `200` `WithdrawalDestination`.

Does not accept `idempotency_key`.

## `withdrawals`

### `create`

Request a withdrawal.

```php
$raims->withdrawals->create($params, $options);
```

`POST /v1/withdrawals` · `CreateWithdrawal`

Request a Pix withdrawal to the company's own default key registered in the Dashboard. The requested amount includes the fee. Store the identifier and track the same withdrawal while its result is pending.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Yes | Total amount to withdraw, in Brazilian real cents, including the fee. Consult withdrawal conditions to check the balance, fee, and limits before requesting. |

Returns: `200` `Withdrawal`, `202` `Withdrawal`.

Accepts `idempotency_key` in `$options`.

### `all`

List withdrawals.

```php
$raims->withdrawals->all($params, $options);
```

`GET /v1/withdrawals` · `ListWithdrawals`

Retrieve your company withdrawal history. Use status and period filters to track transfers and check statement outflows. The request uses the environment of your API key.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `page_size` | `int` | No | Withdrawals per page. |
| `after` | `string` | No | next_after cursor returned on the previous page. Keep the same filters and query sort order. |
| `before` | `string` | No | previous_before cursor returned on the next page. Keep the same filters and query sort order. |
| `status` | `string` | No | Filter by one or more comma-separated states: PENDING, PROCESSING, COMPLETED, FAILED, or CANCELED. |
| `requested_gte` | `int` | No | Return withdrawals requested at or after this instant, inclusive. Supply Unix seconds UTC. |
| `requested_lte` | `int` | No | Return withdrawals requested at or before this instant, inclusive. Supply Unix seconds UTC. |
| `sort_order` | `'asc'\|'desc'` | No | Sort by request date: desc shows the newest first; asc shows the oldest first. |

Returns: `200` `WithdrawalListResponse`.

Does not accept `idempotency_key`.

### `retrieve`

Get a withdrawal.

```php
$raims->withdrawals->retrieve($id, $options);
```

`GET /v1/withdrawals/{id}` · `GetWithdrawal`

Retrieve the status, amounts, and destination of a withdrawal by identifier. Use the same ID shown in withdrawal history or the statement reference.

`id`: Withdrawal identifier with the wd_ prefix.

Returns: `200` `Withdrawal`.

Does not accept `idempotency_key`.

### `retrieveLimits`

Get withdrawal conditions.

```php
$raims->withdrawals->retrieveLimits($options);
```

`GET /v1/withdrawals/limits` · `GetWithdrawalLimits`

Retrieve the available balance, minimum, fee, and withdrawal limits to plan a withdrawal. The request does not reserve funds or limits and does not guarantee that a request will be accepted.

Returns: `200` `WithdrawalLimits`.

Does not accept `idempotency_key`.

### `retrieveSummary`

Get withdrawals in progress.

```php
$raims->withdrawals->retrieveSummary($options);
```

`GET /v1/withdrawals/summary` · `GetWithdrawalSummary`

Retrieve the count and total amount of company withdrawals that are pending or processing. This request requires a Production API key and does not change request progress.

Returns: `200` `WithdrawalSummary`.

Does not accept `idempotency_key`.

### `retrieveAutomaticSettings`

Get automatic withdrawal settings.

```php
$raims->withdrawals->retrieveAutomaticSettings($options);
```

`GET /v1/withdrawals/automatic_settings` · `GetAutomaticWithdrawalSettings`

Retrieve activation, minimum amount, daily time, and the next scheduled withdrawal attempt for the company. This request requires a Production API key.

Returns: `200` `AutomaticWithdrawalSettings`.

Does not accept `idempotency_key`.

### `updateAutomaticSettings`

Update automatic withdrawals.

```php
$raims->withdrawals->updateAutomaticSettings($params, $options);
```

`PATCH /v1/withdrawals/automatic_settings` · `UpdateAutomaticWithdrawalSettings`

Enable, disable, or adjust the daily withdrawal attempt for the selected destination owned by your company. Use a Production API key. Omitted fields preserve their current values; saving does not request an immediate withdrawal.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `enabled` | `bool` | No | Enable or disable new withdrawal attempts. Disabling does not cancel withdrawals already requested. |
| `minimum_amount_cents` | `int` | No | Minimum balance for attempting a withdrawal, in Brazilian real cents. The amount withdrawn depends on the available balance and remaining Pix limit. |
| `time` | `string` | No | Daily time in HH:mm format, using the São Paulo time zone. |

Returns: `200` `AutomaticWithdrawalSettings`.

Accepts `idempotency_key` in `$options`.

## `simulations->charges`

### `confirmPayment`

Confirm payment.

```php
$raims->simulations->charges->confirmPayment($id, $options);
```

`POST /v1/simulations/charges/{id}/confirm_payment` · `ConfirmSimulationPayment`

Confirm payment of a test charge and check the credit to the Sandbox balance.

`id`: Public identifier of the charge created in your company's Sandbox.

Returns: `200` `ChargeSimulation`.

Accepts `idempotency_key` in `$options`.

### `failPayment`

Simulate a payment failure.

```php
$raims->simulations->charges->failPayment($id, $options);
```

`POST /v1/simulations/charges/{id}/fail_payment` · `FailSimulationPayment`

Simulate failure of an open charge to test handling of this result in Sandbox.

`id`: Public identifier of the charge created in your company's Sandbox.

Returns: `200` `ChargeSimulation`.

Accepts `idempotency_key` in `$options`.

### `configureScenario`

Prepare a scenario.

```php
$raims->simulations->charges->configureScenario($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/scenario` · `ConfigureSimulationScenario`

Choose a pending or failed result for Pix or boleto issuance, or charge cancellation in Sandbox. Then perform the action on the same charge.

`id`: Public identifier of the charge created in your company's Sandbox.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `action` | `'pix_issuance'\|'boleto_issuance'\|'cancellation'` | Yes | Action to test: pix_issuance for Pix issuance, boleto_issuance for boleto issuance, or cancellation for cancellation. For cancellation, keep the charge open with Pix or boleto already issued, without confirming payment. |
| `outcome` | `'pending'\|'failed'` | Yes | Action result: pending leaves confirmation open; failed presents a failure. |

Returns: `200` `ChargeScenario`.

Accepts `idempotency_key` in `$options`.

### `resolveScenario`

Resolve a pending scenario.

```php
$raims->simulations->charges->resolveScenario($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/resolve` · `ResolveSimulationScenario`

Complete or fail an issuance or cancellation that remained pending in the test scenario. The action must already have been started on the charge.

`id`: Public identifier of the charge created in your company's Sandbox.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `action` | `'pix_issuance'\|'boleto_issuance'\|'cancellation'` | Yes | Action to test: pix_issuance for Pix issuance, boleto_issuance for boleto issuance, or cancellation for cancellation. |
| `outcome` | `'completed'\|'failed'` | Yes | Result for the pending action: completed finishes the action; failed confirms failure. |

Returns: `200` `ChargeScenario`.

Accepts `idempotency_key` in `$options`.

### `issuePayment`

Issue a test payment.

```php
$raims->simulations->charges->issuePayment($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/issue_payment` · `IssueSimulationPayment`

Issue Pix or boleto for an existing charge in Sandbox. A scenario prepared for that issuance determines whether it remains pending or fails.

`id`: Public identifier of the charge created in your company's Sandbox.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `payment_method` | `int` | Yes | Method to issue: 1 for Pix or 2 for boleto. It must be enabled on the charge. |

Returns: `200` `Charge`, `202` `Charge`.

Accepts `idempotency_key` in `$options`.

### `compensateBoleto`

Simulate boleto clearing.

```php
$raims->simulations->charges->compensateBoleto($id, $options);
```

`POST /v1/simulations/charges/{id}/compensate_boleto` · `CompensateSimulationBoleto`

Record a boleto as cleared, still awaiting settlement. This step does not credit the balance or make a completed payment receipt available.

`id`: Public identifier of the charge created in your company's Sandbox.

Returns: `200` `ChargeBoletoCompensation`.

Accepts `idempotency_key` in `$options`.

### `createRefund`

Simulate a pending refund.

```php
$raims->simulations->charges->createRefund($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/refunds` · `CreateSimulationRefund`

Start a test Pix refund that awaits confirmation. Use the returned ID to complete the refund or simulate a failure.

`id`: Identifier of the charge that received the Pix payment.

| Field in `$params` | Type | Required | Description |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Yes | Amount requested for refund, in Brazilian real cents. To refund everything, supply the remaining amount shown by the eligibility query. |
| `description` | `string\|null` | No | Refund request description. You may leave this field empty. |

Returns: `200` `Refund`, `202` `Refund`.

Accepts `idempotency_key` in `$options`.

## `simulations->refunds`

### `complete`

Complete a simulated refund.

```php
$raims->simulations->refunds->complete($id, $options);
```

`POST /v1/simulations/refunds/{id}/complete` · `CompleteSimulationRefund`

Complete a pending Pix refund in Sandbox. The amount is not deducted from the balance again.

`id`: Test refund identifier returned by Simulate a pending refund.

Returns: `200` `Refund`.

Accepts `idempotency_key` in `$options`.

### `fail`

Simulate a refund failure.

```php
$raims->simulations->refunds->fail($id, $options);
```

`POST /v1/simulations/refunds/{id}/fail` · `FailSimulationRefund`

Simulate failure of a pending Pix refund in Sandbox and check that the amount returns to the available balance.

`id`: Test refund identifier returned by Simulate a pending refund.

Returns: `200` `Refund`.

Accepts `idempotency_key` in `$options`.

## Public value constants

Use `Raims\EnumValues` for named contract values. Names identify the source schema or endpoint and field; `QUERY` identifies a URL filter.
Numeric values receive names only when the contract provides public labels; other values remain documented on their fields.
Constants do not change the values sent to the API.

```php
use Raims\EnumValues;

$params = ['payment_method' => EnumValues::CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX];
```

| Constant in `EnumValues` | Value | Public contract field |
| --- | --- | --- |
| `BALANCE_FORECAST_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_TODAY` | `'today'` | `BalanceForecast.period` |
| `CHARGE_CREATED_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `ChargeCreated.payment_method_types` |
| `CHARGE_CREATED_PAYMENT_METHOD_TYPES_PIX` | `1` | `ChargeCreated.payment_method_types` |
| `CHARGE_LIST_ITEM_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `ChargeListItem.payment_method_types` |
| `CHARGE_LIST_ITEM_PAYMENT_METHOD_TYPES_PIX` | `1` | `ChargeListItem.payment_method_types` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_AWAITING_PAYMENT` | `'awaiting_payment'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_COMPENSATED` | `'compensated'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_SETTLED` | `'settled'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_AVAILABLE` | `'available'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_FAILED` | `'failed'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_NOT_GENERATED` | `'not_generated'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_PENDING` | `'pending'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_AVAILABLE` | `'available'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_FAILED` | `'failed'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_NOT_GENERATED` | `'not_generated'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_PENDING` | `'pending'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `Charge.payment_method_types` |
| `CHARGE_PAYMENT_METHOD_TYPES_PIX` | `1` | `Charge.payment_method_types` |
| `CHARGE_SCENARIO_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_ACTION_CANCELLATION` | `'cancellation'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_OUTCOME_COMPLETED` | `'completed'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_OUTCOME_FAILED` | `'failed'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_OUTCOME_PENDING` | `'pending'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_REQUEST_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_ACTION_CANCELLATION` | `'cancellation'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_OUTCOME_FAILED` | `'failed'` | `ChargeScenarioRequest.outcome` |
| `CHARGE_SCENARIO_REQUEST_OUTCOME_PENDING` | `'pending'` | `ChargeScenarioRequest.outcome` |
| `CHARGE_SCENARIO_STATE_COMPLETED` | `'completed'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_CONFIGURED` | `'configured'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_FAILED` | `'failed'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_PENDING` | `'pending'` | `ChargeScenario.state` |
| `CHARGE_STATISTICS_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_TODAY` | `'today'` | `ChargeStatistics.period` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_INVALID_IMAGE` | `'CHECKOUT_ASSET_INVALID_IMAGE'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_PROCESSING_FAILED` | `'CHECKOUT_ASSET_PROCESSING_FAILED'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_UPLOAD_NOT_FOUND` | `'CHECKOUT_ASSET_UPLOAD_NOT_FOUND'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_STATUS_ABSENT` | `'absent'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_FAILED` | `'failed'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_PENDING` | `'pending'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_READY` | `'ready'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_UPLOAD_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CheckoutAssetUpload.content_type` |
| `CHECKOUT_ASSET_UPLOAD_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CheckoutAssetUpload.content_type` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_BOLETO` | `2` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_INDEFINIDO` | `0` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX` | `1` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `CreateChargeRequest.payment_method_types` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `CreateChargeRequest.payment_method_types` |
| `CREATE_CHECKOUT_FAVICON_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CreateCheckoutFaviconUploadRequest.content_type` |
| `CREATE_CHECKOUT_FAVICON_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CreateCheckoutFaviconUploadRequest.content_type` |
| `CREATE_CHECKOUT_LOGO_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CreateCheckoutLogoUploadRequest.content_type` |
| `CREATE_CHECKOUT_LOGO_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CreateCheckoutLogoUploadRequest.content_type` |
| `CREATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `CreatePaymentLinkRequest.payment_method_types` |
| `CREATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `CreatePaymentLinkRequest.payment_method_types` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_CANCELED` | `'charge.canceled'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_CREATED` | `'charge.created'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_FAILED` | `'charge.failed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_PAID` | `'charge.paid'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_COMPLETED` | `'refund.completed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_FAILED` | `'refund.failed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_REQUESTED` | `'refund.requested'` | `CreateWebhookRequest.events` |
| `CUSTOMER_CREATED_TAX_ID_TYPE_CNPJ` | `'cnpj'` | `CustomerCreated.tax_id_type` |
| `CUSTOMER_CREATED_TAX_ID_TYPE_CPF` | `'cpf'` | `CustomerCreated.tax_id_type` |
| `CUSTOMER_LIST_ITEM_TAX_ID_TYPE_CNPJ` | `'cnpj'` | `CustomerListItem.tax_id_type` |
| `CUSTOMER_LIST_ITEM_TAX_ID_TYPE_CPF` | `'cpf'` | `CustomerListItem.tax_id_type` |
| `DISPUTE_INFRACTION_RESULT_AGREED` | `'agreed'` | `Dispute.infraction_result` |
| `DISPUTE_INFRACTION_RESULT_DISAGREED` | `'disagreed'` | `Dispute.infraction_result` |
| `DISPUTE_KIND_CHARGEBACK` | `'chargeback'` | `Dispute.kind` |
| `DISPUTE_KIND_MED` | `'med'` | `Dispute.kind` |
| `DISPUTE_LIST_ITEM_KIND_CHARGEBACK` | `'chargeback'` | `DisputeListItem.kind` |
| `DISPUTE_LIST_ITEM_KIND_MED` | `'med'` | `DisputeListItem.kind` |
| `DISPUTE_LIST_ITEM_STAGE_CANCELLED` | `'cancelled'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_CLOSED` | `'closed'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_FAILED` | `'failed'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_IN_ANALYSIS` | `'in_analysis'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_OPEN` | `'open'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_REFUND_IN_ANALYSIS` | `'refund_in_analysis'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_REJECTED` | `'rejected'` | `DisputeListItem.stage` |
| `DISPUTE_REFUND_RESULT_PARTIALLY_ACCEPTED` | `'partially_accepted'` | `Dispute.refund_result` |
| `DISPUTE_REFUND_RESULT_REJECTED` | `'rejected'` | `Dispute.refund_result` |
| `DISPUTE_REFUND_RESULT_TOTALLY_ACCEPTED` | `'totally_accepted'` | `Dispute.refund_result` |
| `DISPUTE_STAGE_CANCELLED` | `'cancelled'` | `Dispute.stage` |
| `DISPUTE_STAGE_CLOSED` | `'closed'` | `Dispute.stage` |
| `DISPUTE_STAGE_FAILED` | `'failed'` | `Dispute.stage` |
| `DISPUTE_STAGE_IN_ANALYSIS` | `'in_analysis'` | `Dispute.stage` |
| `DISPUTE_STAGE_OPEN` | `'open'` | `Dispute.stage` |
| `DISPUTE_STAGE_REFUND_IN_ANALYSIS` | `'refund_in_analysis'` | `Dispute.stage` |
| `DISPUTE_STAGE_REJECTED` | `'rejected'` | `Dispute.stage` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_TODAY` | `'today'` | `GetBalanceForecast.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_TODAY` | `'today'` | `GetChargeStatistics.query.period` |
| `LIST_CHARGES_QUERY_SORT_KEY_AMOUNT_CENTS` | `'amount_cents'` | `ListCharges.query.sort_key` |
| `LIST_CHARGES_QUERY_SORT_KEY_CREATED_AT` | `'created_at'` | `ListCharges.query.sort_key` |
| `LIST_CHARGES_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListCharges.query.sort_order` |
| `LIST_CHARGES_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListCharges.query.sort_order` |
| `LIST_DISPUTES_QUERY_SORT_KEY_CREATED_AT` | `'created_at'` | `ListDisputes.query.sort_key` |
| `LIST_DISPUTES_QUERY_SORT_KEY_DISPUTED_AMOUNT_CENTS` | `'disputed_amount_cents'` | `ListDisputes.query.sort_key` |
| `LIST_DISPUTES_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListDisputes.query.sort_order` |
| `LIST_DISPUTES_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListDisputes.query.sort_order` |
| `LIST_STATEMENTS_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListStatements.query.sort_order` |
| `LIST_STATEMENTS_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListStatements.query.sort_order` |
| `LIST_WITHDRAWALS_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListWithdrawals.query.sort_order` |
| `LIST_WITHDRAWALS_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListWithdrawals.query.sort_order` |
| `PAYMENT_LINK_DETAILS_EFFECTIVE_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLinkDetails.effective_payment_method_types` |
| `PAYMENT_LINK_DETAILS_EFFECTIVE_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLinkDetails.effective_payment_method_types` |
| `PAYMENT_LINK_DETAILS_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLinkDetails.payment_method_types` |
| `PAYMENT_LINK_DETAILS_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLinkDetails.payment_method_types` |
| `PAYMENT_LINK_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLink.payment_method_types` |
| `PAYMENT_LINK_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLink.payment_method_types` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_BOLETO_AWAITING_SETTLEMENT` | `'BOLETO_AWAITING_SETTLEMENT'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_CHARGE_EXPIRED` | `'CHARGE_EXPIRED'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_CHARGE_NOT_OPEN` | `'CHARGE_NOT_OPEN'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PAYMENT_LINK_PAUSED` | `'PAYMENT_LINK_PAUSED'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PAYMENT_METHODS_UNAVAILABLE` | `'PAYMENT_METHODS_UNAVAILABLE'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PIX_CANCELLATION_PENDING` | `'PIX_CANCELLATION_PENDING'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PIX_CANCELLED` | `'PIX_CANCELLED'` | `PaymentLinkUnavailableReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_FAILURE_REASON_UNAVAILABLE` | `'REFUND_FAILURE_REASON_UNAVAILABLE'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_NOT_SUBMITTED` | `'REFUND_NOT_SUBMITTED'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_PROCESSING_FAILED` | `'REFUND_PROCESSING_FAILED'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_PROCESSING_UNAVAILABLE` | `'REFUND_PROCESSING_UNAVAILABLE'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_REJECTED` | `'REFUND_REJECTED'` | `RefundFailureReason.code` |
| `REFUND_STATUS_ACCEPTED` | `'accepted'` | `Refund.status` |
| `REFUND_STATUS_COMPLETED` | `'completed'` | `Refund.status` |
| `REFUND_STATUS_FAILED` | `'failed'` | `Refund.status` |
| `REFUND_STATUS_RESERVED` | `'reserved'` | `Refund.status` |
| `REFUND_STATUS_UNKNOWN` | `'unknown'` | `Refund.status` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_ALREADY_COMPLETED` | `'PIX_REFUND_ALREADY_COMPLETED'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_CASHIN_NOT_FOUND` | `'PIX_REFUND_CASHIN_NOT_FOUND'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_INSUFFICIENT_BALANCE` | `'PIX_REFUND_INSUFFICIENT_BALANCE'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_IN_PROGRESS` | `'PIX_REFUND_IN_PROGRESS'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_NOT_AVAILABLE` | `'PIX_REFUND_NOT_AVAILABLE'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_PROVIDER_ACCOUNT_NOT_READY` | `'PIX_REFUND_PROVIDER_ACCOUNT_NOT_READY'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_PROVIDER_UNAVAILABLE` | `'PIX_REFUND_PROVIDER_UNAVAILABLE'` | `RefundUnavailableReason.code` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_CANCELLATION` | `'cancellation'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_OUTCOME_COMPLETED` | `'completed'` | `ResolveChargeScenarioRequest.outcome` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_OUTCOME_FAILED` | `'failed'` | `ResolveChargeScenarioRequest.outcome` |
| `STATEMENT_REFERENCE_TYPE_AJUSTE` | `4` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_COBRANCA` | `1` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_CONTESTACAO` | `2` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_MANUAL` | `5` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_SAQUE` | `3` | `Statement.reference_type` |
| `STATEMENT_STATEMENT_TYPE_BLOQUEIO` | `9` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_CREDITO_POR_AJUSTE` | `7` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEBITO_POR_AJUSTE` | `8` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEVOLUCAO` | `5` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEVOLUCAO_PARCIAL` | `6` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_LIBERACAO` | `10` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_RECEBIMENTO` | `1` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_SAQUE` | `4` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_TARIFA` | `2` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_TARIFA_DE_SERVICO` | `3` | `Statement.statement_type` |
| `UPDATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `UpdatePaymentLinkRequest.payment_method_types` |
| `UPDATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `UpdatePaymentLinkRequest.payment_method_types` |
| `WEBHOOK_CREATED_DISABLED_REASON_CONTINUOUS_FAILURE` | `'continuous_failure'` | `WebhookCreated.disabled_reason` |
| `WEBHOOK_CREATED_DISABLED_REASON_MANUAL` | `'manual'` | `WebhookCreated.disabled_reason` |
| `WEBHOOK_DISABLED_REASON_CONTINUOUS_FAILURE` | `'continuous_failure'` | `Webhook.disabled_reason` |
| `WEBHOOK_DISABLED_REASON_MANUAL` | `'manual'` | `Webhook.disabled_reason` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_CANCELED` | `'charge.canceled'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_CREATED` | `'charge.created'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_FAILED` | `'charge.failed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_PAID` | `'charge.paid'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_COMPLETED` | `'refund.completed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_FAILED` | `'refund.failed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_REQUESTED` | `'refund.requested'` | `WebhookEventType.code` |
| `WEBHOOK_HEALTH_STATUS_DISABLED` | `'disabled'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_FAILING` | `'failing'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_HEALTHY` | `'healthy'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_UNKNOWN` | `'unknown'` | `WebhookHealth.status` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_CNPJ` | `'CNPJ'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_EMAIL` | `'EMAIL'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_EVP` | `'EVP'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_PHONE` | `'PHONE'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_FAILURE_REASON_CODE_ABOVE_MAXIMUM_WITHDRAWAL` | `'ABOVE_MAXIMUM_WITHDRAWAL'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_ACCOUNT_UNAVAILABLE` | `'BANK_ACCOUNT_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_PROCESSING_FAILURE` | `'BANK_PROCESSING_FAILURE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_TIMEOUT` | `'BANK_TIMEOUT'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_UNSUPPORTED` | `'BANK_UNSUPPORTED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BELOW_MINIMUM_WITHDRAWAL` | `'BELOW_MINIMUM_WITHDRAWAL'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BLOCKED_BALANCE` | `'BLOCKED_BALANCE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_DUPLICATED_REQUEST` | `'DUPLICATED_REQUEST'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INSUFFICIENT_BALANCE` | `'INSUFFICIENT_BALANCE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INTERNAL_ERROR` | `'INTERNAL_ERROR'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INVALID_ACCOUNT_TYPE` | `'INVALID_ACCOUNT_TYPE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INVALID_BANK_ACCOUNT` | `'INVALID_BANK_ACCOUNT'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_RISK_POLICY_REJECTED` | `'RISK_POLICY_REJECTED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_FAILURE_REASON_UNAVAILABLE` | `'WITHDRAWAL_FAILURE_REASON_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_LIMIT_REACHED` | `'WITHDRAWAL_LIMIT_REACHED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_TEMPORARILY_UNAVAILABLE` | `'WITHDRAWAL_TEMPORARILY_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_CNPJ` | `'CNPJ'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_EMAIL` | `'EMAIL'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_EVP` | `'EVP'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_PHONE` | `'PHONE'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_STATUS_CANCELED` | `'CANCELED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_COMPLETED` | `'COMPLETED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_FAILED` | `'FAILED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_PENDING` | `'PENDING'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_PROCESSING` | `'PROCESSING'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_UNKNOWN` | `'UNKNOWN'` | `Withdrawal.status` |
