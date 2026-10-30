<?php

declare(strict_types=1);

namespace Raims;

use Raims\Exception\InvalidArgumentException;
use Raims\Exception\RuntimeException;
use Raims\Service\AccountsService;
use Raims\Service\BalancesService;
use Raims\Service\ChargesService;
use Raims\Service\CustomersService;
use Raims\Service\DisputesService;
use Raims\Service\PaymentLinksService;
use Raims\Service\RefundsService;
use Raims\Service\SimulationsService;
use Raims\Service\StatementsService;
use Raims\Service\WebhookEventTypesService;
use Raims\Service\WebhooksService;
use Raims\Service\WithdrawalDestinationsService;
use Raims\Service\WithdrawalsService;

/**
 * An isolated API client. Credentials and options are never stored globally.
 */
final class RaimsClient
{
    public const VERSION = '0.1.1';
    public const API_BASE = 'https://api.raims.com';

    public readonly AccountsService $accounts;
    public readonly BalancesService $balances;
    public readonly ChargesService $charges;
    public readonly CustomersService $customers;
    public readonly DisputesService $disputes;
    public readonly PaymentLinksService $paymentLinks;
    public readonly RefundsService $refunds;
    public readonly SimulationsService $simulations;
    public readonly StatementsService $statements;
    public readonly WebhookEventTypesService $webhookEventTypes;
    public readonly WebhooksService $webhooks;
    public readonly WithdrawalDestinationsService $withdrawalDestinations;
    public readonly WithdrawalsService $withdrawals;

    /**
     * @param string|array{api_key: string, api_base?: string, timeout?: float, connect_timeout?: float, max_network_retries?: int, http_client?: HttpClient\HttpClientInterface, request_observer?: callable(RequestDiagnostics): void} $config
     */
    public function __construct(#[\SensitiveParameter] string|array $config)
    {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException('The Raims SDK requires a 64-bit PHP runtime.');
        }
        if (is_string($config)) {
            $config = ['api_key' => $config];
        }
        $apiKey = $config['api_key'] ?? null;
        if (!is_string($apiKey)) {
            throw new InvalidArgumentException('api_key must be a string.');
        }
        unset($config['api_key']);
        $requestor = new ApiRequestor($apiKey, $config);
        $this->accounts = new AccountsService($requestor);
        $this->balances = new BalancesService($requestor);
        $this->charges = new ChargesService($requestor);
        $this->customers = new CustomersService($requestor);
        $this->disputes = new DisputesService($requestor);
        $this->paymentLinks = new PaymentLinksService($requestor);
        $this->refunds = new RefundsService($requestor);
        $this->simulations = new SimulationsService($requestor);
        $this->statements = new StatementsService($requestor);
        $this->webhookEventTypes = new WebhookEventTypesService($requestor);
        $this->webhooks = new WebhooksService($requestor);
        $this->withdrawalDestinations = new WithdrawalDestinationsService($requestor);
        $this->withdrawals = new WithdrawalsService($requestor);
    }

    public function __debugInfo(): array
    {
        return ['version' => self::VERSION];
    }
}
