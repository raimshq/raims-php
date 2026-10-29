<?php

declare(strict_types=1);

namespace Raims\Service;

use Raims\ApiRequestor;

abstract class AbstractService
{
    public function __construct(protected readonly ApiRequestor $requestor)
    {
    }
}
