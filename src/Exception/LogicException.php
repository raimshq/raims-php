<?php

declare(strict_types=1);

namespace Raims\Exception;

/** An SDK object was used in an unsupported state. */
class LogicException extends \LogicException implements ExceptionInterface
{
}
