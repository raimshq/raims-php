<?php

declare(strict_types=1);

namespace Raims;

/**
 * Cursor metadata shared by the named page models in the public contract.
 *
 * @property-read int $page_size
 * @property-read bool $has_next
 * @property-read bool $has_previous
 * @property-read string|null $next_after
 * @property-read string|null $previous_before
 */
abstract class Pagination extends ApiResource
{
}
