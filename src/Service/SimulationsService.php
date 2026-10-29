<?php

declare(strict_types=1);

namespace Raims\Service;

use Raims\ApiRequestor;

/** Published simulation endpoints require a Sandbox API key. */
final class SimulationsService
{
    public readonly SimulationChargesService $charges;
    public readonly SimulationRefundsService $refunds;

    public function __construct(ApiRequestor $requestor)
    {
        $this->charges = new SimulationChargesService($requestor);
        $this->refunds = new SimulationRefundsService($requestor);
    }
}
