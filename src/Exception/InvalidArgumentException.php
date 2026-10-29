<?php

declare(strict_types=1);

namespace Raims\Exception;

/** Invalid configuration or parameters supplied to the SDK. */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
