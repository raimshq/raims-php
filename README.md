# Raims PHP

SDK PHP para a API pública da Raims. O cliente organiza os 67 endpoints publicados
em serviços, com respostas tipadas, paginação por cursor e validação de webhooks.
Os nomes dos campos e as regras de cada requisição seguem o contrato da API.

Os objetos aninhados e os itens dos iteradores mantêm seus tipos no editor.
O transporte reutiliza conexões e oferece diagnósticos opcionais de cada
tentativa HTTP, incluindo a decisão de retry, sem parâmetros, corpos ou credenciais.

Instale o pacote pelo Composer:

```text
composer require raims/raims-php:^0.1.0
```

O pacote está no [Packagist](https://packagist.org/packages/raims/raims-php).
Consulte a [instalação](docs/installation.md) para requisitos, atualizações
e uso de um checkout local.

## Requisitos

PHP 8.2 ou superior em 64 bits, Composer 2 e extensões `curl`, `json` e `hash`.
O SDK usa cURL e não exige bibliotecas de terceiros em produção.

## Primeiro uso

Depois de instalar o pacote no seu projeto:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Raims\RaimsClient;

$apiKey = getenv('RAIMS_API_KEY');
if ($apiKey === false || $apiKey === '') {
    throw new RuntimeException('Configure RAIMS_API_KEY no servidor.');
}

$raims = new RaimsClient($apiKey);
$customers = $raims->customers->all(['page_size' => 20]);

foreach ($customers as $customer) {
    echo $customer->id, PHP_EOL;
}
```

A URL-base é `https://api.raims.com` tanto para Sandbox quanto para Produção.
A chave determina a empresa, o ambiente e as permissões. Use o SDK no servidor
e mantenha as credenciais fora do navegador e do controle de versão.

## Criar um cliente e uma cobrança

Com a instância `$raims` do exemplo anterior, cadastre o cliente e use o `id`
retornado para criar a cobrança. Neste exemplo, `$customerTaxId` é o CPF ou CNPJ
válido do cliente autorizado pela sua aplicação. `$customerIdempotencyKey` e
`$chargeIdempotencyKey` são chaves distintas, criadas e persistidas pela aplicação
antes das requisições para identificar cada intenção.

`$chargeDueDate` é o vencimento escolhido e persistido para o pedido, em segundos
Unix UTC. Ao repetir uma requisição após perder a resposta, reutilize a mesma
chave e os mesmos parâmetros, incluindo esse vencimento.

O valor é um inteiro em centavos; `15000` representa R$ 150,00. Datas da API usam
Unix segundos UTC. Se o documento já existir na mesma conta e ambiente, a
requisição atualiza esse cadastro e mantém seu identificador.

```php
$customer = $raims->customers->create([
    'tax_id' => $customerTaxId,
], [
    'idempotency_key' => $customerIdempotencyKey,
]);

$charge = $raims->charges->create([
    'customer_id' => $customer->id,
    'amount_cents' => 15000,
    'due_date' => $chargeDueDate,
    'title' => 'Pedido 1234',
], [
    'idempotency_key' => $chargeIdempotencyKey,
]);

$status = $charge->getLastResponse()->statusCode;
$requestId = $charge->getLastResponse()->getRequestId();
```

Uma resposta `202` exige acompanhar o recurso. O recebimento da resposta não
confirma, por si só, pagamento ou conclusão financeira. O SDK não gera chaves
de idempotência nem repete escritas automaticamente. Leia as
[regras de idempotência e erros](docs/errors-and-retries.md).

## Serviços

| Propriedade | Recursos |
| --- | --- |
| `$raims->accounts` | Dados da conta, tarifas e personalização do checkout |
| `$raims->balances` | Saldo e previsão |
| `$raims->customers` | Cadastro e consulta de clientes |
| `$raims->charges` | Cobranças, pagamento, documentos e estornos por cobrança |
| `$raims->paymentLinks` | Links de pagamento e solicitação de envio por e-mail |
| `$raims->refunds` | Consulta de estornos e recibos |
| `$raims->disputes` | Contestações |
| `$raims->statements` | Extratos |
| `$raims->withdrawals` | Saques, limites, resumo e configuração automática |
| `$raims->withdrawalDestinations` | Destinos de saque |
| `$raims->webhooks` | Cadastro, pausa, saúde, teste e rotação de segredo |
| `$raims->webhookEventTypes` | Tipos de evento disponíveis |
| `$raims->simulations->charges` | Simulações de cobrança com chave Sandbox |
| `$raims->simulations->refunds` | Simulações de estorno com chave Sandbox |

`create`, `retrieve`, `update` e `all` seguem o mesmo padrão entre os serviços.
A [referência gerada](docs/reference.md) lista todas as assinaturas, os campos de
entrada, os retornos e o suporte à idempotência.

## Documentação

- [Instalação e requisitos](docs/installation.md)
- [Configuração, respostas, arquivos e atualizações parciais](docs/usage.md)
- [Paginação](docs/pagination.md)
- [Erros, idempotência e retries](docs/errors-and-retries.md)
- [Recebimento e validação de webhooks](docs/webhooks.md)
- [Referência dos 67 endpoints](docs/reference.md)
- [Contrato fixado e manutenção](docs/maintenance.md)
- [Contribuição](CONTRIBUTING.md) e [segurança](SECURITY.md)
- [Documentação da API](https://docs.raims.com/br/api)

## Licença

Distribuído sob a licença [MIT](LICENSE).
