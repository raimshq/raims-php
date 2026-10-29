# Uso do cliente

## Configuração

Cada instância mantém sua própria credencial. Use instâncias distintas quando
seu processo atender contas diferentes, ou informe `api_key` explicitamente
nas opções de uma requisição.

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'timeout' => 80.0,
    'connect_timeout' => 10.0,
    'max_network_retries' => 0,
]);

$customer = $raims->customers->retrieve($customerId, [
    'timeout' => 60.0,
]);
```

| Opção do cliente | Padrão | Efeito |
| --- | --- | --- |
| `api_key` | Obrigatória | Chave enviada como Bearer |
| `api_base` | `https://api.raims.com` | Origem HTTPS, sem caminho, query ou credenciais |
| `timeout` | `80.0` | Prazo máximo de cada tentativa, em segundos |
| `connect_timeout` | `10.0` | Prazo de conexão, limitado pelo prazo da tentativa |
| `max_network_retries` | `0` | De zero a cinco retries adicionais somente em leituras |
| `http_client` | `CurlClient` | Implementação de `HttpClientInterface` |
| `request_observer` | Desativado | Callback com os metadados de cada tentativa HTTP |

`api_base` só aceita HTTP em loopback para desenvolvimento. Trocar a origem não
seleciona o ambiente da chave. Nunca configure uma origem que você não controla:
ela receberá a credencial.

As opções por requisição são `api_key`, `idempotency_key`, `timeout` e
`max_network_retries`. Também podem ser fornecidas em `new RequestOptions([...])`.
Headers arbitrários não são aceitos. A API define o `Request-Id` de cada tentativa.

Um transporte personalizado deve lançar `ApiConnectionException` quando não
receber a resposta, para permitir o tratamento e os retries de leitura do SDK.
Exceções da sua aplicação não são convertidas automaticamente em falhas de rede.

O `CurlClient` reutiliza conexões entre chamadas da mesma instância. Headers,
corpo, método e callbacks são reiniciados a cada chamada, inclusive quando a
chave muda. Para controlar a duração desse cache explicitamente, injete o
transporte e use `close()` quando encerrar seu trabalho:

```php
$httpClient = new \Raims\HttpClient\CurlClient();
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'http_client' => $httpClient,
]);

try {
    $customers = $raims->customers->all();
} finally {
    $httpClient->close();
}
```

Uma chamada posterior ao mesmo transporte abre outra conexão. O fechamento
também ocorre quando o transporte deixa de ser referenciado pelo processo.

## Diagnósticos opcionais

Configure um callback para observar cada tentativa, incluindo retries, falhas
de conexão e respostas `204`:

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'request_observer' => static function (\Raims\RequestDiagnostics $event): void {
        error_log(json_encode($event, JSON_THROW_ON_ERROR));
    },
]);
```

`RequestDiagnostics` contém somente `operationId`, `method`, `path`, `attempt`,
`durationMs`, `httpStatus`, `requestId`, `transportErrorCode`, `willRetry` e
`retryDelayMs`. O caminho é o
template do endpoint, como `/v1/customers/{id}`; ele não inclui o identificador
recebido nem a query. `attempt` começa em 1. A duração mede apenas a tentativa,
sem incluir a espera entre retries. Status e Request-Id são nulos quando a
resposta não foi recebida; o código de transporte depende do cliente HTTP usado.

`willRetry` indica se o SDK decidiu fazer outra tentativa automática. Quando
verdadeiro, `retryDelayMs` informa a espera planejada em milissegundos; caso
contrário, o intervalo é `null`. O callback recebe essa decisão antes da espera.
Sucessos, tentativas finais, escritas e recusas que exigem mais de 30 segundos
de espera têm `willRetry: false`. O campo não confirma a execução de uma nova
tentativa se o processo for interrompido.

Esses metadados descrevem o transporte. HTTP `202` continua sendo um resultado
pendente, e um HTTP `200` ainda pode ser recusado pela validação da estrutura.
Agrupe falhas na sua aplicação antes de emitir alertas. O SDK não envia logs ou
telemetria por conta própria. Exceções do callback são isoladas para preservar
o resultado da requisição; trate falhas do seu coletor dentro do próprio callback.
Chamadas feitas pelo callback não geram notificações recursivas ao mesmo observer.

## Respostas

Os serviços retornam classes em `Raims\Model`, com propriedades documentadas
para o editor e hidratação dos objetos aninhados. Os recursos são somente leitura:

```php
$charge = $raims->charges->retrieve($chargeId);
$id = $charge->id;
$sameId = $charge['id'];
$pix = $charge->payment_information->pix;
$data = $charge->toArray();
$json = json_encode($charge, JSON_THROW_ON_ERROR);

$http = $charge->getLastResponse();
$status = $http->statusCode;
$requestId = $http->getRequestId();
$replayed = $http->getHeader('Idempotency-Replayed');
```

Campos novos da API são preservados. As classes descrevem o contrato fixado,
mas não convertem valores monetários para `float`, não traduzem estados e não
alteram os valores recebidos. O SDK requer PHP 64 bits para os inteiros da API.
Representações JSON equivalentes, como `1`, `1.0` e `1e0`, são entregues como
inteiros quando cabem no runtime. Essa leitura preserva a precisão dos inteiros
grandes; valores acima da capacidade do PHP permanecem texto. Frações continuam
sendo recusadas nos campos inteiros. O corpo em `$http->body` mantém o texto
original da resposta.

O SDK confere os campos obrigatórios e os tipos estruturais antes de construir
o recurso. Uma lista sem paginação obrigatória, um item escalar no lugar de um
objeto ou um campo obrigatório ausente gera `UnexpectedResponseException`.
Campos novos e novos valores de enum são preservados; o SDK não repete as
validações de formato, limites ou regras de negócio do servidor.

`getLastResponse()` está disponível no recurso principal. Objetos aninhados não
representam outra requisição. `DeleteWebhook` retorna `null` após um `204` sem
corpo. O SDK não acompanha automaticamente respostas `202`; consulte o recurso
correspondente para confirmar o resultado.

O corpo bruto em `$http->body`, `toArray()` e a serialização podem conter dados
pessoais, segredos ou URLs temporárias. Use-os somente onde houver necessidade.
O debug padrão dos objetos do SDK oculta o conteúdo; isso não substitui os
controles de acesso e de logs da sua aplicação.

## Valores públicos nomeados

Use `Raims\EnumValues` para evitar strings e números repetidos na integração:

```php
use Raims\EnumValues;

$params = [
    'customer_id' => $customerId,
    'amount_cents' => 15000,
    'due_date' => time() + 86400,
    'payment_method' => EnumValues::CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX,
];
```

As constantes são geradas dos campos públicos do contrato. Valores numéricos
recebem nomes somente quando o contrato fornece rótulos explícitos. Consulte
o catálogo no fim da [referência](reference.md); não há conversão automática
dos valores recebidos nem uma lista manual de estados mantida pelo SDK.

## Omissão, null e mapas vazios

As entradas são arrays PHP com os nomes do contrato em `snake_case`. O SDK não
remove `null`, `false`, zero ou texto vazio do corpo. Uma atualização parcial
segue as regras do endpoint:

```php
// Omitir description preserva a descrição da cobrança.
$raims->charges->update($chargeId, ['title' => 'Pedido atualizado']);

// Na edição de cobrança, null limpa a descrição.
$raims->charges->update($chargeId, ['description' => null]);

// Um mapa vazio precisa ser um objeto JSON, não uma lista JSON.
$raims->customers->update($customerId, ['metadata' => (object) []]);
```

`metadata` tem regras de mesclagem e remoção próprias; não presuma que um mapa
vazio apaga tudo. Na edição de links, remover uma URL exige o respectivo campo
`clear_success_redirect_url` ou `clear_cancel_redirect_url`. Consulte o contrato
do endpoint antes de atribuir significado a `null` ou texto vazio.

## Documentos e uploads

Recibos e boletos retornam um objeto `FinancialDocument` com `url` e `expires_at`.
O endpoint autenticado não retorna os bytes do PDF:

```php
$document = $raims->charges->retrieveReceipt($chargeId);
$temporaryUrl = $document->url;
$expiresAt = $document->expires_at;
```

Baixe a URL temporária em um GET separado, sem `Authorization`, cookies ou chave
de API, e sem modificar seus parâmetros. A URL permite acesso até a expiração;
não a registre em logs. Se expirar, consulte o endpoint autenticado novamente.
O SDK não segue nem baixa essas URLs pelo transporte autenticado.

Para personalizar as imagens do checkout:

1. Prepare o envio com `accounts->createCheckoutLogoUpload($params)` ou
   `createCheckoutFaviconUpload($params)`, usando tipo e tamanho reais do arquivo.
2. Faça um PUT separado para `upload_url`, com os bytes e os valores exatos de
   `Content-Type` e `Content-Length`, sem chave de API, cookies ou redirects.
3. Use o ID retornado em `accounts->updateCheckoutSettings($params)`. Uma resposta
   `202` exige acompanhar a aplicação da imagem.

Os campos aceitos estão na [referência](reference.md). O SDK não envia o arquivo
nem aplica a imagem automaticamente. Mantenha validação de destino, tamanho,
tipo, expiração e conclusão no componente que executar o PUT.
