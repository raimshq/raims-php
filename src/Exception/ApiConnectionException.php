<?php

declare(strict_types=1);

namespace Raims\Exception;

use Raims\HttpClient\HttpResponse;

/** The response was not received. A write may still have reached the API. */
class ApiConnectionException extends ApiErrorException
{
    public function __construct(
        string $message,
        ?HttpResponse $response = null,
        #[\SensitiveParameter] ?array $error = null,
        private readonly ?int $transportErrorCode = null,
    ) {
        parent::__construct($message, $response, $error);
    }

    public function getTransportErrorCode(): ?int
    {
        return $this->transportErrorCode;
    }
}
