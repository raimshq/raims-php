<?php

declare(strict_types=1);

namespace Raims\Exception;

/** A resource contains an unexpected value or pagination state. */
class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface
{
}
