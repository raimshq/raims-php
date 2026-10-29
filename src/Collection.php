<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\LogicException;
use Raims\Exception\UnexpectedValueException;

/**
 * A page of resources. Iterating the page performs no additional requests.
 *
 * @template T of ApiResource
 * @implements \IteratorAggregate<int, T>
 * @property-read Pagination|null $page
 * @property-read list<T> $list
 */
class Collection extends ApiResource implements \IteratorAggregate, \Countable
{
    private ?\Closure $pageLoader = null;
    private array $pageParams = [];

    /** @internal Attached by ApiRequestor to preserve the original filters and options. */
    public function setPageLoader(\Closure $loader, array $params): void
    {
        $this->pageLoader = $loader;
        $this->pageParams = $params;
    }

    /** @return \Traversable<int, T> */
    public function getIterator(): \Traversable
    {
        yield from ($this->list ?? []);
    }

    public function count(): int
    {
        return count($this->list ?? []);
    }

    /** @return T|null */
    public function first(): ?ApiResource
    {
        return $this->list[0] ?? null;
    }

    /** @return T|null */
    public function last(): ?ApiResource
    {
        $items = $this->list ?? [];

        return $items === [] ? null : $items[array_key_last($items)];
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function nextPage(): ?static
    {
        return $this->loadPage('has_next', 'next_after', 'after', 'before');
    }

    public function previousPage(): ?static
    {
        return $this->loadPage('has_previous', 'previous_before', 'before', 'after');
    }

    private function loadPage(string $flag, string $cursorName, string $parameter, string $opposite): ?static
    {
        if (!$this->page?->{$flag}) {
            return null;
        }
        $cursor = $this->page->{$cursorName};
        if (!is_string($cursor) || $cursor === '') {
            throw new UnexpectedValueException('Pagination indicates another page without a valid cursor.');
        }
        if ($this->pageLoader === null) {
            throw new LogicException('This collection has no API page loader.');
        }
        $params = $this->pageParams;
        unset($params[$opposite]);
        $params[$parameter] = $cursor;
        $page = ($this->pageLoader)($params);
        if (!$page instanceof static) {
            throw new UnexpectedValueException('Pagination returned an unexpected response type.');
        }

        return $page;
    }

    /**
     * Lazily fetch following pages, including empty pages that carry a next cursor.
     *
     * @return \Generator<int, T, void, void>
     */
    public function autoPagingIterator(): \Generator
    {
        $page = $this;
        $seen = [];
        if (isset($this->pageParams['after'])) {
            $seen[$this->pageParams['after']] = true;
        }
        do {
            foreach ($page as $item) {
                yield $item;
            }
            if (!$page->page?->has_next) {
                return;
            }
            $cursor = $page->page->next_after;
            if (!is_string($cursor) || $cursor === '' || isset($seen[$cursor])) {
                throw new UnexpectedValueException('Pagination returned an invalid or repeated cursor.');
            }
            $seen[$cursor] = true;
            $page = $page->nextPage();
        } while ($page !== null);
    }
}
