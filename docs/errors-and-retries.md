# Erros, idempotência e retries

## Tratar erros

Decida pelo status HTTP e por `error.code`. As mensagens da API são diagnósticos
em inglês e podem mudar. Os detalhes de validação identificam `field`, `code` e
`message` quando aplicáveis.

```php
use Raims\Exception\ApiConnectionException;
use Raims\Exception\ApiErrorException;
use Raims\Exception\ExceptionInterface;
use Raims\Exception\RateLimitException;

try {
    $customer = $raims->customers->retrieve($customerId);
} catch (RateLimitException $error) {
    $retryAfterSeconds = $error->getRetryAfter();
    $requestId = $error->getRequestId();
    // Reagendar a leitura conforme os limites da aplicação.
} catch (ApiConnectionException $error) {
    // A resposta não foi recebida. Uma escrita pode ter sido processada.
} catch (ApiErrorException $error) {
    $status = $error->getHttpStatus();
    $code = $error->getErrorCode();
    $details = $error->getError()['details'] ?? [];
    // Exibir uma mensagem apropriada, sem registrar dados pessoais.
} catch (ExceptionInterface $error) {
    // Tratar outra falha do SDK, como configuração ou argumento inválido.
}
```

| Exceção | Condição |
| --- | --- |
| `InvalidRequestException` | HTTP 400, 405, 413, 415 ou 422 |
| `AuthenticationException` | HTTP 401 |
| `PermissionException` | HTTP 403 |
| `NotFoundException` | HTTP 404 |
| `ConflictException` | HTTP 409 |
| `RateLimitException` | HTTP 429 |
| `ApiException` | Demais falhas HTTP, incluindo 5xx |
| `UnexpectedResponseException` | Redirect, status de sucesso inesperado, JSON inválido ou estrutura de resposta incompatível |
| `ApiConnectionException` | Falha de transporte ou timeout |

As exceções emitidas pelas interfaces públicas do SDK implementam
`Raims\Exception\ExceptionInterface`. Use esse tipo para capturar falhas da
biblioteca; mantenha os catches específicos quando precisar tratar cada condição.
Erros nativos de tipo do PHP e exceções da sua aplicação seguem seus próprios tipos.

As falhas HTTP derivam de `ApiErrorException`. Erros de configuração e de entrada
do cliente usam `Raims\Exception\InvalidArgumentException`, que também é uma
`\InvalidArgumentException` do PHP. A mesma compatibilidade vale para os tipos
`LogicException`, `UnexpectedValueException` e `RuntimeException` do SDK.
Uma resposta HTML de uma borda pode
não ter `error.code`; os accessors retornam `null` quando o dado não existe.

`getHttpHeaders()` disponibiliza os headers recebidos, incluindo
`Raims-Rate-Limited-Reason` quando presente. Prefira registrar apenas código,
status e `Request-Id`. Não registre o erro completo, o corpo ou headers livres.
`getRequestId()` prioriza `Request-Id` e aceita `X-Request-Id` como alias.

Em `ApiConnectionException`, `getTransportErrorCode()` retorna o código cURL
quando disponível, ou `null` para um transporte que não forneça esse diagnóstico.
O código permite distinguir timeout e falhas de conexão ou certificado sem
interpretar o texto da mensagem. Ele não confirma se uma escrita foi processada.

## Idempotência

Forneça `idempotency_key` nas opções somente nos endpoints que a declaram:

```php
$charge = $raims->charges->create($params, [
    'idempotency_key' => $persistedKeyForThisOrder,
]);
```

A chave deve ter de 1 a 255 caracteres ASCII visíveis, sem espaços. Gere e
persista sua identidade de negócio antes da chamada. Após perda da resposta,
conserve a mesma chave e a mesma requisição enquanto o resultado puder ser
reutilizado. Não reutilize uma chave para outra intenção nem altere os parâmetros
para contornar uma falha.

O SDK não gera a chave. Quando ela não é informada, o header não é enviado.
O suporte é definido por endpoint; `customers->delete($id)` não o aceita.
Os métodos rejeitam uma chave em endpoints que não declaram esse header.

A política publicada conserva respostas de sucesso e falhas 5xx admitidas por
24 horas; erros 4xx e recusas 503 explicitamente repetíveis não ficam retidos.
Uma resposta retida de erro não confirma o resultado financeiro. Consulte
[idempotência na API](https://docs.raims.com/br/api/idempotencia) antes de decidir
por uma nova tentativa.

`IDEMPOTENCY_REQUEST_IN_PROGRESS` e `IDEMPOTENCY_RESULT_UNKNOWN` exigem ações
diferentes. No primeiro caso, respeite `Retry-After`. No segundo, consulte e
reconcilie o recurso; não troque a chave nem repita a intenção financeira.

## Retries

O padrão é zero retries. `max_network_retries` entre 1 e 5 habilita tentativas
adicionais somente para GET/HEAD, em falhas de conexão e HTTP 408, 429, 500,
502, 503 ou 504. Os métodos de produto publicados usam GET para essas leituras.

```php
$raims = new \Raims\RaimsClient([
    'api_key' => $apiKey,
    'max_network_retries' => 2,
]);
```

O intervalo usa backoff exponencial com jitter entre 250 ms e 2 s, para distribuir
as novas tentativas entre clientes. Um `Retry-After` válido pode ampliar essa
espera para até 30 s. Se ele exigir mais de 30 s, o SDK devolve a falha para que
a aplicação reagende. Cada tentativa tem seu próprio timeout; o tempo total
pode incluir todos os prazos e intervalos.

POST, PATCH e DELETE não são repetidos automaticamente, mesmo com chave de
idempotência ou retries configurados. Uma falha de conexão não demonstra que
o servidor deixou de executar a requisição. Respostas `202` também não iniciam
polling ou uma nova tentativa automaticamente.
