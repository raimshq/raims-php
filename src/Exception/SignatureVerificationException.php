<?php

declare(strict_types=1);

namespace Raims\Exception;

/** Raised when a webhook cannot be authenticated or decoded. */
class SignatureVerificationException extends \UnexpectedValueException implements ExceptionInterface
{
}
