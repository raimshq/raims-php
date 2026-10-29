<?php

declare(strict_types=1);

namespace Raims\Exception;

/** The SDK cannot run in the current environment. */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
