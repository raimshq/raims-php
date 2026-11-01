# Pagination

Paginated lists return `list` and `page`. `all()` fetches one page; iterating
that page does not trigger more requests.

```php
$page = $raims->customers->all(['page_size' => 50]);

foreach ($page as $customer) {
    // Only customers on this page.
}

$count = count($page);
$firstItem = $page->first();
$lastItem = $page->last();
$empty = $page->isEmpty();
$next = $page->nextPage();
$previous = $page->previousPage();
```

`first()` and `last()` return a typed item, or `null` when the page is empty.
`isEmpty()` only indicates whether the current page has no items. These
methods do not fetch other pages. An empty page may have a continuation;
check `page.has_next` or use the automatic iterator to visit the remaining
results.

`nextPage()` returns `null` when `page.has_next` is false. `previousPage()` does
the same for `page.has_previous`. The methods preserve request filters and
options and remove the cursor for the opposite direction.

## Iterate through every page

```php
$page = $raims->charges->all([
    'customer_id' => $customerId,
    'page_size' => 100,
]);

foreach ($page->autoPagingIterator() as $charge) {
    // Process each charge according to the application's authorization.
}
```

The iterator fetches the next page when needed. Leaving the `foreach` stops
further fetches. Each page is an HTTP read and can fail, produce a new
`Request-Id`, or reach a limit. The SDK does not provide a snapshot of the
result set; concurrent changes follow the API listing guarantees.

An empty page may have a continuation, particularly for withdrawal
destinations. The SDK follows `has_next` and `next_after` in that case too.
Repeated, missing, or invalid cursors stop iteration with an exception to
prevent a loop. `autoPagingIterator()` moves forward; use `previousPage()`
explicitly to move backward.

## Manual control

```php
$first = $raims->customers->all(['page_size' => 20]);
if ($first->page->has_next) {
    $second = $raims->customers->all([
        'page_size' => 20,
        'after' => $first->page->next_after,
    ]);
}
```

Copy cursors returned by the API. Do not calculate positions from IDs or send
`after` and `before` together. Keep the same filters and sort order. The
webhook event type list contains only `list`, without pagination: use
`$raims->webhookEventTypes->all()->list`.
