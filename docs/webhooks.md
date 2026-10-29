# Receber webhooks

Use `Raims\Webhook` para autenticar o corpo original antes de interpretar o evento.
A assinatura usa o protocolo da Raims e os headers `webhook-id`,
`webhook-timestamp` e `webhook-signature`.

```php
use Raims\Exception\SignatureVerificationException;
use Raims\Webhook;

$payload = file_get_contents('php://input');
if ($payload === false) {
    throw new RuntimeException('Não foi possível ler o corpo da requisição.');
}

// Os valores vêm dos headers recebidos pelo servidor HTTP.
$headers = [
    'webhook-id' => $_SERVER['HTTP_WEBHOOK_ID'] ?? '',
    'webhook-timestamp' => $_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? '',
    'webhook-signature' => $_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '',
];

try {
    $event = Webhook::constructEvent($payload, $headers, $webhookSecret);
} catch (SignatureVerificationException $error) {
    http_response_code(400);
    exit;
}

// Antes do aceite HTTP, valide o evento e grave-o de forma durável e deduplicada.
// Execute essa etapa com os componentes da sua aplicação.
```

O segredo é o `secret_key` retornado ao criar ou rotacionar o webhook. Não use
a chave Bearer da API nesse argumento. Guarde o segredo em um cofre; consultas
posteriores do cadastro não o retornam. Não registre o payload nem o segredo.

## O que o helper verifica

`constructEvent()` exige headers válidos, timestamp dentro da tolerância,
assinatura correspondente ao segredo e corpo JSON do tipo objeto. O retorno
é um array com os campos recebidos, sem validação de negócio do evento.
`verifySignature()` faz a mesma autenticação sem interpretar JSON e retorna `void`.

O padrão de tolerância é 300 segundos, tanto para o passado quanto para o futuro:

```php
$event = Webhook::constructEvent($payload, $headers, $webhookSecret, 300);
```

Mantenha o relógio do servidor sincronizado. Tolerância zero exige o mesmo
segundo da tentativa; ela não desativa a proteção temporal. Valores negativos
são inválidos. O argumento opcional `now` permite injetar um relógio em testes.

Os nomes dos headers não diferenciam maiúsculas de minúsculas. O helper aceita
valores string ou listas com um valor; headers de assinatura duplicados ou
ambíguos são recusados. Não reconstrua nem reserialize o JSON antes de verificar:
espaços, Unicode e quebras de linha fazem parte dos bytes assinados.

## Rotação e processamento

Durante a transição de segredo, o header pode conter várias assinaturas `v1=`
separadas por vírgula. O helper aceita qualquer assinatura que corresponda ao
segredo configurado. Use `previous_secret_expires_at` para coordenar a atualização
dos servidores da sua aplicação durante uma rotação.

A verificação criptográfica não impede o processamento duplicado de uma entrega
válida dentro da tolerância. Antes de responder com sucesso:

1. Confira tipo, ambiente e recurso do evento conforme sua integração.
2. Deduplicate pelo identificador do evento em armazenamento durável. O `id` do
   corpo identifica o evento; `webhook-id` identifica a entrega e não o substitui.
3. Grave o trabalho de forma atômica antes do aceite HTTP. Processe o efeito de
   negócio com idempotência; se a gravação falhar, devolva uma falha HTTP.

O campo `created_at` dos eventos usa ISO 8601. Ele não é o timestamp Unix do header
da tentativa nem segue automaticamente o formato das datas dos recursos da API.
Consulte o [guia de recebimento](https://docs.raims.com/br/integracoes/webhooks/receber-eventos)
para os eventos e as condições de entrega.
