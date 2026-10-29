# Referência do SDK PHP

Esta referência é gerada do contrato público fixado. Os campos mantêm os nomes da API.
Consulte os exemplos e as condições de uso na [referência HTTP](https://docs.raims.com/br/api).

## `accounts`

### `retrieveFees`

Consultar tarifas da conta.

```php
$raims->accounts->retrieveFees($options);
```

`GET /v1/accounts/fees` · `GetAccountFees`

Consulte as condições comerciais vigentes da sua empresa para recebimentos por Pix e boleto.

Retorno: `200` `AccountFees`.

Não aceita `idempotency_key`.

### `retrieveNumber`

Recuperar número de conta.

```php
$raims->accounts->retrieveNumber($options);
```

`GET /v1/accounts/account_number` · `GetAccountNumber`

Consulte a agência e o número da conta financeira da sua empresa na Raims. A consulta exige uma chave de Produção e uma conta aprovada e habilitada.

Retorno: `200` `AccountNumber`.

Não aceita `idempotency_key`.

### `retrieveCheckoutSettings`

Consultar personalização do checkout.

```php
$raims->accounts->retrieveCheckoutSettings($options);
```

`GET /v1/accounts/checkout_settings` · `GetCheckoutSettings`

Consulte as imagens, as cores, o e-mail de suporte e os meios de pagamento padrão do checkout da empresa no ambiente da chave.

Retorno: `200` `CheckoutSettings`.

Não aceita `idempotency_key`.

### `updateCheckoutSettings`

Atualizar personalização do checkout.

```php
$raims->accounts->updateCheckoutSettings($params, $options);
```

`PATCH /v1/accounts/checkout_settings` · `UpdateCheckoutSettings`

Altere as imagens, as cores e o e-mail de suporte do checkout. Campos omitidos preservam o valor atual. O envio de uma nova imagem pode exigir acompanhamento até sua aplicação.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `support_email` | `string\|null` | Não | E-mail de suporte apresentado no checkout. Omitir preserva o valor; null ou texto vazio remove o contato. |
| `hex_primary_color` | `string` | Não | Cor principal do checkout no formato #RRGGBB. Omitir preserva o valor; null ou texto vazio não são aceitos. |
| `hex_secondary_color` | `string` | Não | Cor de fundo do checkout no formato #RRGGBB. Omitir preserva o valor; null ou texto vazio não são aceitos. |
| `logo_upload_id` | `string\|null` | Não | ID retornado ao preparar o envio da logo. Envie o arquivo antes de associá-lo. Omitir preserva a imagem atual; null a remove. |
| `favicon_upload_id` | `string\|null` | Não | ID retornado ao preparar o envio do ícone. Envie o arquivo antes de associá-lo. Omitir preserva a imagem atual; null a remove. |

Retorno: `200` `CheckoutSettings`, `202` `CheckoutSettings`.

Aceita `idempotency_key` em `$options`.

### `createCheckoutLogoUpload`

Preparar envio da logo.

```php
$raims->accounts->createCheckoutLogoUpload($params, $options);
```

`POST /v1/accounts/checkout_settings/logo_uploads` · `CreateCheckoutLogoUpload`

Obtenha uma URL temporária para enviar a imagem. Depois do envio dos bytes, associe o ID retornado à personalização do checkout. A preparação não altera a imagem atual.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `content_type` | `'image/png'\|'image/jpeg'` | Sim | Tipo de conteúdo do arquivo: image/png ou image/jpeg. |
| `file_size` | `int` | Sim | Tamanho do arquivo em bytes. |
| `width` | `int` | Sim | Largura da imagem em pixels. |
| `height` | `int` | Sim | Altura da imagem em pixels. |

Retorno: `201` `CheckoutAssetUpload`.

Aceita `idempotency_key` em `$options`.

### `createCheckoutFaviconUpload`

Preparar envio do ícone.

```php
$raims->accounts->createCheckoutFaviconUpload($params, $options);
```

`POST /v1/accounts/checkout_settings/favicon_uploads` · `CreateCheckoutFaviconUpload`

Obtenha uma URL temporária para enviar a imagem. Depois do envio dos bytes, associe o ID retornado à personalização do checkout. A preparação não altera a imagem atual.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `content_type` | `'image/png'\|'image/jpeg'` | Sim | Tipo de conteúdo do arquivo: image/png ou image/jpeg. |
| `file_size` | `int` | Sim | Tamanho do arquivo em bytes. |
| `width` | `int` | Sim | Largura do ícone em pixels. Deve ser igual à altura. |
| `height` | `int` | Sim | Altura do ícone em pixels. Deve ser igual à largura. |

Retorno: `201` `CheckoutAssetUpload`.

Aceita `idempotency_key` em `$options`.

### `deleteCheckoutAssetUpload`

Descartar envio de imagem.

```php
$raims->accounts->deleteCheckoutAssetUpload($id, $options);
```

`DELETE /v1/accounts/checkout_settings/uploads/{id}` · `DeleteCheckoutAssetUpload`

Descarte um envio de logo ou ícone que ainda não foi associado ao checkout. Um envio expirado também pode ser descartado. Para remover uma imagem já aplicada, use o PATCH da personalização.

`id`: Identificador recebido ao preparar o envio da imagem.

Retorno: `200` `CheckoutAssetUploadDeleted`.

Aceita `idempotency_key` em `$options`.

## `balances`

### `retrieve`

Recuperar saldo atual.

```php
$raims->balances->retrieve($options);
```

`GET /v1/balances` · `GetBalance`

Consulta os valores total, bloqueado e disponível na empresa e no ambiente da chave de API. Os valores são expressos em centavos de real. O saldo disponível considera os bloqueios e não confirma a autorização de um saque ou estorno.

Retorno: `200` `Balance`.

Não aceita `idempotency_key`.

### `forecast`

Consultar recebimentos previstos.

```php
$raims->balances->forecast($params, $options);
```

`GET /v1/balances/forecast` · `GetBalanceForecast`

Consulte a estimativa de recebimentos pendentes no período e o saldo total projetado. Os valores são estimativas e não indicam disponibilidade para saque.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `period` | `'today'\|'last_7_days'\|'last_15_days'\|'last_30_days'` | Não | Período das datas previstas de recebimento, pelo calendário de São Paulo. Inclui hoje e os dias anteriores conforme a opção. Não consulta vencimentos nem projeta os próximos dias. |

Retorno: `200` `BalanceForecast`.

Não aceita `idempotency_key`.

## `charges`

### `create`

Criar nova cobrança.

```php
$raims->charges->create($params, $options);
```

`POST /v1/charges` · `CreateCharge`

Cria uma cobrança para um cliente já cadastrado. Informe o valor em centavos. Sem lista própria de meios, payment_method 1 solicita Pix e 2 solicita boleto, com os dados disponíveis em payment_information. Boleto usa nome, e-mail válido, CPF/CNPJ e endereço do cliente. Logradouro, bairro, cidade e UF ausentes podem ser preenchidos a partir do CEP quando disponíveis; número e complemento vêm do cadastro. Com lista própria, a emissão ocorre após a escolha no checkout. Use create_checkout true para obter o link sem solicitar e-mail. Uma URL de retorno ou send_email true também gera o link; somente send_email true solicita envio de e-mail.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `customer_id` | `string` | Sim | ID de um cliente da empresa e do ambiente da chave de API. Copie o id retornado no cadastro ou na consulta. Pix direto exige CPF/CNPJ válido. Boleto direto exige nome, e-mail válido, CPF/CNPJ e endereço, incluindo CEP e número; complete esses dados no cadastro do cliente. |
| `amount_cents` | `int` | Sim | Valor da cobrança em centavos de real. Por exemplo, 10000 representa R$ 100,00. |
| `payment_method` | `0\|1\|2` | Não | Meio da cobrança. Omitir ou enviar 0 permite escolher no checkout. Sem payment_method_types, 1 solicita Pix e 2 solicita boleto na criação, conforme os meios habilitados. Boleto usa o endereço do cliente e completa campos ausentes pelo CEP quando disponíveis. Com lista própria, envie 0 ou omita este campo; a emissão acontece após a escolha no checkout. |
| `due_date` | `int` | Sim | Vencimento em segundos Unix UTC, pelo menos cinco minutos no futuro. A data máxima é hoje mais dez anos, no calendário de São Paulo. |
| `title` | `string\|null` | Não | Título da cobrança. Você pode deixar este campo vazio. |
| `description` | `string\|null` | Não | Descrição da cobrança. Você pode deixar este campo vazio. |
| `send_email` | `bool` | Não | Com true, solicita o envio da cobrança ao e-mail do cliente e gera checkout_url. Em contas pendentes, o cliente precisa ter o mesmo e-mail principal da conta. Fora dessa condição, um e-mail ausente ou inválido impede o envio, mas permite criar a cobrança quando não houver emissão direta de boleto. Boleto direto exige e-mail válido mesmo com false. A resposta não confirma a entrega da mensagem. |
| `create_checkout` | `bool` | Não | Com true, gera o link retornado em checkout_url, sem exigir uma URL de retorno nem solicitar envio de e-mail. Com false, o link ainda é gerado quando send_email é true ou uma URL de retorno é informada. |
| `success_redirect_url` | `string\|null` | Não | URL HTTPS do seu site para retorno após a confirmação do pagamento. O redirecionamento não comprova o pagamento; consulte a cobrança para confirmá-lo. Não aceita credenciais no endereço, IP literal, localhost ou domínio Raims. Quando preenchida, gera checkout_url mesmo com send_email false. Se omitida, vazia ou null, o retorno não é definido. |
| `cancel_redirect_url` | `string\|null` | Não | URL HTTPS do seu site para o pagador voltar pelo checkout. Voltar não cancela a cobrança, o Pix ou o boleto. Não aceita credenciais no endereço, IP literal, localhost ou domínio Raims. Quando preenchida, gera checkout_url mesmo com send_email false. Se omitida, vazia ou null, o retorno não é definido. |
| `payment_method_types` | `list<1\|2>\|null` | Não | Meios permitidos para a cobrança: 1 para Pix e 2 para boleto. Omitir ou enviar null usa a configuração de checkout herdada. Informe uma lista sem repetições. A configuração própria substitui o padrão da conta; uma configuração do link pode apenas restringir essa lista. Combine com payment_method omitido ou 0. |
| `external_reference` | `string\|null` | Não | Referência do pedido ou da cobrança no seu sistema. Null, vazio ou somente espaços deixam sem referência. Pode ser repetida e não evita cobranças duplicadas. É diferente da referência do cadastro do cliente. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | Não | Dados de integração associados à cobrança, em pares de chave e texto. Chaves diferenciam maiúsculas de minúsculas. Omitir, enviar null ou {} cria a cobrança sem esses dados; pares com valor null não são incluídos. Texto vazio é um valor válido. |

Retorno: `201` `ChargeCreated`, `202` `ChargeCreated`.

Aceita `idempotency_key` em `$options`.

### `all`

Listar cobranças.

```php
$raims->charges->all($params, $options);
```

`GET /v1/charges` · `ListCharges`

Lista cobranças da empresa e do ambiente da sua chave de API, com filtros e paginação por cursor. Para avançar, use page.next_after em after enquanto page.has_next for true, mantendo os filtros e a ordenação. Alterações de status podem levar alguns segundos para aparecer nos filtros; aguarde antes de consultar novamente.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de cobranças por página. Inteiros positivos acima do máximo usam o máximo. Valor omitido, vazio, zero, negativo ou inválido usa 10. |
| `after` | `string` | Não | Copie page.next_after da resposta anterior para avançar. Mantenha os mesmos filtros e a ordenação; não envie before junto. Deixe vazio para começar a listagem. |
| `before` | `string` | Não | Copie page.previous_before da resposta anterior para voltar. Mantenha os mesmos filtros e a ordenação; não envie after junto. Deixe vazio se não quiser voltar. |
| `identifier` | `string` | Não | Filtra pelo ID completo da cobrança. Se não houver correspondência, list será vazio. |
| `customer_id` | `string` | Não | Filtra pelo ID público completo do cliente, retornado no cadastro ou na consulta. O vínculo permanece o mesmo se nome ou e-mail mudar. Pode ser combinado com os demais filtros; mantenha o mesmo customer_id ao mudar de página. Um ID válido sem cobranças na empresa e no ambiente da chave retorna list vazio. Vazio ou ausência não aplica o filtro. |
| `external_reference` | `string` | Não | Filtra pela referência atual da cobrança no seu sistema, com igualdade do valor completo, incluindo maiúsculas, minúsculas e acentos. Vazio ou ausência não aplica o filtro. Pode ser combinado com os demais filtros; preserve o valor ao navegar por cursor. Após criar ou editar uma cobrança, pode levar alguns segundos para a busca refletir a referência. |
| `email` | `string` | Não | Filtra pelo e-mail completo registrado na cobrança. Alterar o cadastro do cliente não altera esse e-mail. |
| `status` | `string` | Não | Filtra por um ou mais códigos separados por vírgula, como 0,1. 0 criado, 1 aguardando pagamento, 2 autorizado, 3 em processamento, 4 pago, 5 parcialmente devolvido, 6 devolvido, 7 cancelado, 8 expirado, 9 falha, 10 chargeback, 11 chargeback em análise, 12 chargeback perdido e 13 chargeback ganho. Pode levar alguns segundos para refletir alterações. |
| `payment_method` | `string` | Não | Filtra pelo meio de pagamento da cobrança: 1 para Pix e 2 para boleto. Para combinar, envie 1,2. Uma cobrança com meio indefinido pode passar a usar Pix ou boleto quando o código Pix ou boleto é gerado no checkout. A seleção na tela, por si só, não altera o filtro. |
| `created_gte` | `int` | Não | Filtra cobranças criadas a partir deste instante, inclusive, em segundos Unix UTC. Se informar created_lte, este valor deve ser menor ou igual a ele. |
| `created_lte` | `int` | Não | Filtra cobranças criadas até este instante, inclusive, em segundos Unix UTC. |
| `due_gte` | `int` | Não | Filtra cobranças com vencimento a partir deste instante, inclusive, em segundos Unix UTC. Pode ser combinado com due_lte e os demais filtros; mantenha o valor ao mudar de página. |
| `due_lte` | `int` | Não | Filtra cobranças com vencimento até este instante, inclusive, em segundos Unix UTC. Se informar due_gte, este valor deve ser maior ou igual a ele. Pode ser usado sozinho ou com os demais filtros. |
| `paid_gte` | `int` | Não | Filtra cobranças pagas a partir deste instante, inclusive, em segundos Unix UTC. Usa a data do pagamento confirmado, mesmo quando houver estorno posterior. Cobranças sem pagamento confirmado ficam fora do resultado. Mantenha o valor ao mudar de página. |
| `paid_lte` | `int` | Não | Filtra cobranças pagas até este instante, inclusive, em segundos Unix UTC. Se informar paid_gte, este valor deve ser maior ou igual a ele. Pode ser combinado com os demais filtros. |
| `sort_key` | `'amount_cents'\|'created_at'` | Não | Ordena por valor em centavos ou data de criação. Se omitido, usa a ordem de cadastro; com filtro de criação, usa created_at. Mantenha a mesma ordenação ao mudar de página. |
| `sort_order` | `'asc'\|'desc'` | Não | Define a direção da ordenação. Com desc, a ordem padrão mostra as cobranças mais recentes primeiro. |

Retorno: `200` `ChargeListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar uma única cobrança.

```php
$raims->charges->retrieve($id, $options);
```

`GET /v1/charges/{id}` · `GetCharge`

Consulta os dados, o status e as instruções de pagamento disponíveis de uma cobrança da empresa e do ambiente da sua chave de API.

`id`: Identificador público da cobrança na Raims, recebido na criação ou na listagem de cobranças.

Retorno: `200` `Charge`.

Não aceita `idempotency_key`.

### `update`

Atualizar cobrança existente.

```php
$raims->charges->update($id, $params, $options);
```

`PATCH /v1/charges/{id}` · `UpdateCharge`

Atualiza título, descrição, referência externa e dados de integração de uma cobrança da empresa e do ambiente da sua chave de API. Você pode alterar somente metadata mesmo após o pagamento ou estorno. Alterações nos demais campos continuam sujeitas ao estado da cobrança; se algum campo não puder ser alterado, nenhuma mudança da requisição será aplicada.

`id`: Identificador público da cobrança na Raims, recebido na criação ou na listagem de cobranças.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `title` | `string\|null` | Não | Título da cobrança. Omita para manter o título atual; envie null ou texto vazio para removê-lo. |
| `description` | `string\|null` | Não | Descrição da cobrança. Omita para manter a descrição atual; envie null ou texto vazio para removê-la. |
| `external_reference` | `string\|null` | Não | Referência do pedido ou da cobrança no seu sistema. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. Pode ser repetida em outras cobranças e é independente da referência do cliente. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | Não | Dados de integração associados ao recurso. Informe pares de chave e texto. Chaves diferenciam maiúsculas de minúsculas. Omitir ou enviar {} preserva os dados; null remove todos os pares, e null em uma chave remove somente essa chave. Texto vazio é um valor válido. |

Retorno: `200` `Charge`.

Aceita `idempotency_key` em `$options`.

### `revoke`

Revogar cobrança.

```php
$raims->charges->revoke($id, $options);
```

`DELETE /v1/charges/{id}` · `RevokeCharge`

Revoga uma cobrança em aberto da empresa e do ambiente da chave de API, preservando seu histórico. No Sandbox, o cancelamento é simulado. Em Produção, a revogação pode aguardar confirmação.

`id`: Identificador público da cobrança na Raims, recebido na criação ou na listagem de cobranças.

Retorno: `200` `ChargeRevoked`, `202` `ChargeRevocationPending`.

Aceita `idempotency_key` em `$options`.

### `retrievePayment`

Consultar dados do pagamento.

```php
$raims->charges->retrievePayment($id, $options);
```

`GET /v1/charges/{id}/payment` · `GetChargePayment`

Consulte o valor, a data e o meio de pagamento confirmado de uma cobrança. Nos recebimentos Pix, end_to_end_id informa o identificador bancário quando disponível. A cobrança precisa estar paga, parcialmente estornada ou com contestação ganha, com a data do pagamento registrada. A consulta considera a empresa e o ambiente da sua chave de API.

`id`: Identificador público da cobrança, recebido na criação ou na listagem de cobranças.

Retorno: `200` `PaymentReceipt`.

Não aceita `idempotency_key`.

### `retrieveReceipt`

Baixar recibo de pagamento.

```php
$raims->charges->retrieveReceipt($id, $options);
```

`GET /v1/charges/{id}/receipt` · `GetChargeReceipt`

Obtenha uma URL temporária para baixar o recibo de um pagamento confirmado em PDF. A cobrança precisa estar paga, parcialmente estornada ou com contestação ganha, com a data do pagamento registrada. O recibo informa o valor original da cobrança paga; devoluções têm comprovantes próprios. O documento é emitido pela Raims e não substitui nota fiscal nem comprovante bancário. A requisição exige a chave de API da mesma empresa e ambiente.

`id`: Identificador público da cobrança cujo recibo você deseja baixar.

Retorno: `200` `FinancialDocument`.

Não aceita `idempotency_key`.

### `retrieveBoleto`

Baixar boleto.

```php
$raims->charges->retrieveBoleto($id, $options);
```

`GET /v1/charges/{id}/boleto` · `GetChargeBoleto`

Obtenha uma URL temporária para baixar o PDF de um boleto já emitido, inclusive antes do pagamento. A consulta exige a chave da mesma empresa e ambiente e não emite nem renova o boleto. O documento não comprova pagamento. No Sandbox, o PDF identifica a simulação e não deve ser usado para pagar.

`id`: Identificador público da cobrança cujo boleto você deseja baixar.

Retorno: `200` `FinancialDocument`.

Não aceita `idempotency_key`.

### `createRefund`

Solicitar estorno.

```php
$raims->charges->createRefund($id, $params, $options);
```

`POST /v1/charges/{id}/refunds` · `CreateRefund`

Solicita a devolução integral ou parcial de um pagamento Pix recebido. Em Produção, o valor retorna à conta de origem e uma solicitação aceita ainda precisa de confirmação. No Sandbox, a simulação conclui na própria requisição, sem movimentar dinheiro real.

`id`: Identificador da cobrança que recebeu o pagamento Pix.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Sim | Valor solicitado para devolução, em centavos de real. Para devolver tudo, informe o valor restante mostrado na consulta de elegibilidade. |
| `description` | `string\|null` | Não | Descrição da solicitação de devolução. Você pode deixar este campo vazio. |

Retorno: `200` `Refund`, `202` `Refund`.

Aceita `idempotency_key` em `$options`.

### `allRefunds`

Listar estornos de uma cobrança.

```php
$raims->charges->allRefunds($id, $params, $options);
```

`GET /v1/charges/{id}/refunds` · `ListChargeRefunds`

Lista as solicitações de estorno de uma cobrança, das mais recentes para as mais antigas, com paginação por cursor. A consulta não inicia nem repete devoluções.

`id`: Identificador da cobrança que recebeu o pagamento Pix.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de estornos por página. Valores acima do máximo usam o máximo; ausência, vazio, texto, zero ou valor negativo usa o padrão. |
| `after` | `string` | Não | Cursor recebido em page.next_after para avançar. Copie o valor completo e mantenha a mesma cobrança. Não envie junto de before. |
| `before` | `string` | Não | Cursor recebido em page.previous_before para voltar. Copie o valor completo e mantenha a mesma cobrança. Não envie junto de after. |
| `created_gte` | `int` | Não | Filtra estornos solicitados a partir deste instante, inclusive, em segundos Unix UTC. Usa a data de criação da solicitação, sem depender da conclusão. Mantenha o valor ao mudar de página. |
| `created_lte` | `int` | Não | Filtra estornos solicitados até este instante, inclusive, em segundos Unix UTC. Se informar created_gte, este valor deve ser maior ou igual a ele. |

Retorno: `200` `RefundListResponse`.

Não aceita `idempotency_key`.

### `retrieveRefundEligibility`

Consultar elegibilidade para estorno.

```php
$raims->charges->retrieveRefundEligibility($id, $options);
```

`GET /v1/charges/{id}/refund_eligibility` · `GetRefundEligibility`

Consulta se uma cobrança aceita um pedido de devolução, o valor restante, o máximo disponível para solicitar agora e os valores pendentes. As condições são conferidas novamente ao solicitar o estorno.

`id`: Identificador da cobrança que recebeu o pagamento Pix.

Retorno: `200` `RefundEligibility`.

Não aceita `idempotency_key`.

### `statistics`

Consultar estatísticas de cobranças.

```php
$raims->charges->statistics($params, $options);
```

`GET /v1/charges/statistics` · `GetChargeStatistics`

Consulte quantidades, valores e taxa de pagamento das cobranças para acompanhar seus recebimentos no ERP ou sistema de gestão. A consulta considera a empresa e o ambiente da sua chave de API. Os dados podem levar alguns segundos para refletir alterações nas cobranças.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `period` | `'today'\|'last_7_days'\|'last_15_days'\|'last_30_days'` | Não | Período da consulta, pelo calendário de São Paulo. today considera hoje; last_7_days, last_15_days e last_30_days incluem hoje e os dias anteriores. A consulta aceita somente esses períodos. start_at e end_at são campos da resposta, não filtros de datas personalizadas. |

Retorno: `200` `ChargeStatistics`.

Não aceita `idempotency_key`.

## `customers`

### `create`

Criar cliente.

```php
$raims->customers->create($params, $options);
```

`POST /v1/customers` · `CreateCustomer`

Cria um cliente na empresa e no ambiente da sua chave de API. Se o CPF ou CNPJ já estiver cadastrado, atualiza esse cliente e mantém seu identificador. Ao informar somente o CEP como endereço, logradouro, bairro, cidade e UF podem ser preenchidos automaticamente. Retorna 201 ao criar e 200 ao atualizar.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `full_name` | `string\|null` | Não | Nome completo do pagador. |
| `tax_id` | `string` | Sim | CPF ou CNPJ válido, com ou sem pontos, barra, hífen e espaços. |
| `email` | `string\|null` | Não | E-mail do pagador. Cadastrar o e-mail não envia uma mensagem. |
| `dial_code` | `string\|null` | Não | Código de discagem internacional. Pode ser informado sem phone_local. |
| `phone_local` | `string\|null` | Não | Número de telefone sem o código de discagem, com ou sem pontuação. Pode ser informado sem dial_code. |
| `zip_code` | `string\|null` | Não | CEP do endereço, com ou sem hífen. Se street, neigh, city, uf, street_number e complement estiverem vazios, o CEP pode preencher rua, bairro, cidade e estado. O preenchimento não inclui número nem complemento. |
| `street` | `string\|null` | Não | Logradouro do endereço. |
| `street_number` | `string\|null` | Não | Número do endereço. |
| `complement` | `string\|null` | Não | Complemento do endereço. |
| `neigh` | `string\|null` | Não | Bairro do endereço. |
| `city` | `string\|null` | Não | Cidade do endereço. |
| `uf` | `string\|null` | Não | Sigla do estado, como SP. |
| `external_reference` | `string\|null` | Não | Identificador do cliente no seu sistema. Para um cliente já cadastrado, omita para manter a referência ou envie null ou texto vazio para removê-la. Use external_reference na listagem de clientes para buscar esse valor. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | Não | Dados de integração associados ao recurso. Informe pares de chave e texto. Chaves diferenciam maiúsculas de minúsculas. Omitir ou enviar {} preserva os dados; null remove todos os pares, e null em uma chave remove somente essa chave. Texto vazio é um valor válido. |

Retorno: `200` `CustomerCreated`, `201` `CustomerCreated`.

Aceita `idempotency_key` em `$options`.

### `all`

Listar clientes.

```php
$raims->customers->all($params, $options);
```

`GET /v1/customers` · `ListCustomers`

Lista os clientes da empresa e do ambiente da chave conforme os filtros informados, com paginação por cursor. Envie os parâmetros na URL, sem corpo na requisição.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de clientes por página. Inteiros positivos acima do máximo usam o máximo. Valor omitido, vazio, zero, negativo ou inválido usa 10. |
| `after` | `string` | Não | Copie page.next_after da resposta anterior para avançar. Use o cursor sem alterações, mantenha os mesmos filtros e não envie before junto. |
| `before` | `string` | Não | Copie page.previous_before da resposta anterior para voltar uma página. Mantenha os mesmos filtros e não envie after junto. |
| `identifier` | `string` | Não | Identificador público do cliente, com prefixo cus_. |
| `name` | `string` | Não | Busca pelo nome do cliente, com os cadastros mais recentes primeiro. Deixe vazio para ignorar este filtro. |
| `email` | `string` | Não | Filtra pelo e-mail completo do cliente. |
| `document` | `string` | Não | Filtra pelo CPF ou CNPJ completo do cliente, com ou sem pontos, barra, hífen e espaços. |
| `external_reference` | `string` | Não | Filtra pela referência atual do cliente no seu sistema, com igualdade do valor completo, incluindo maiúsculas, minúsculas e acentos. Pode ser combinada com os demais filtros. Após criar ou editar o cliente, a busca pode levar alguns segundos para refletir a referência. Mantenha o valor ao navegar por cursor. |

Retorno: `200` `CustomerListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um único cliente.

```php
$raims->customers->retrieve($id, $options);
```

`GET /v1/customers/{id}` · `GetCustomer`

Consulta os dados cadastrais de um cliente pelo identificador público na empresa e no ambiente da chave de API. A requisição não tem corpo.

`id`: Identificador público do cliente na Raims, recebido na criação ou na listagem de clientes.

Retorno: `200` `CustomerListItem`.

Não aceita `idempotency_key`.

### `update`

Atualizar cliente existente.

```php
$raims->customers->update($id, $params, $options);
```

`PATCH /v1/customers/{id}` · `UpdateCustomer`

Atualiza os campos enviados do cadastro e os dados de integração do cliente. Campos omitidos mantêm o valor atual. Nos campos de texto, null ou texto vazio limpam o conteúdo; metadata possui as regras indicadas no campo. CPF/CNPJ, identificador e data de criação permanecem iguais. O CEP não preenche outros campos de endereço nesta requisição. Enviar {} retorna o cadastro atual.

`id`: Identificador público do cliente na Raims, recebido na criação ou na listagem de clientes.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `full_name` | `string\|null` | Não | Nome completo do pagador. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `email` | `string\|null` | Não | E-mail do pagador. Atualizar o e-mail não envia uma mensagem. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `dial_code` | `string\|null` | Não | Código de discagem internacional. Pode ser informado sem phone_local. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `phone_local` | `string\|null` | Não | Número de telefone sem o código de discagem, com ou sem pontuação. Pode ser informado sem dial_code. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `zip_code` | `string\|null` | Não | CEP do endereço, com ou sem hífen. Atualizar o CEP não preenche os outros campos de endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `street` | `string\|null` | Não | Logradouro do endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `street_number` | `string\|null` | Não | Número do endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `complement` | `string\|null` | Não | Complemento do endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `neigh` | `string\|null` | Não | Bairro do endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `city` | `string\|null` | Não | Cidade do endereço. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `uf` | `string\|null` | Não | Sigla do estado, como SP. Omita para manter o valor atual; envie null ou texto vazio para removê-lo. |
| `external_reference` | `string\|null` | Não | Identificador do cliente no seu sistema. Omita para manter a referência atual; envie null ou texto vazio para removê-la. Use external_reference na listagem de clientes para buscar esse valor. |
| `metadata` | `array<string, string\|null>\|\stdClass\|null` | Não | Dados de integração associados ao recurso. Informe pares de chave e texto. Chaves diferenciam maiúsculas de minúsculas. Omitir ou enviar {} preserva os dados; null remove todos os pares, e null em uma chave remove somente essa chave. Texto vazio é um valor válido. |

Retorno: `200` `CustomerListItem`.

Aceita `idempotency_key` em `$options`.

### `delete`

Remover cliente.

```php
$raims->customers->delete($id, $options);
```

`DELETE /v1/customers/{id}` · `DeleteCustomer`

Remove um cliente pelo identificador público na empresa e no ambiente da chave de API. A remoção é permitida somente quando não há cobranças vinculadas ainda não removidas, inclusive pagas. Não remove cobranças nem devolve valores. Repetir a requisição para o mesmo cadastro já removido retorna o mesmo identificador com deleted: true. A requisição não tem corpo.

`id`: Identificador público do cliente na Raims, recebido na criação ou na listagem de clientes.

Retorno: `200` `CustomerDeleted`.

Não aceita `idempotency_key`.

### `deleteBatch`

Remover clientes em lote.

```php
$raims->customers->deleteBatch($params, $options);
```

`DELETE /v1/customers` · `DeleteCustomersBatch`

Remove até 100 clientes da empresa e do ambiente da sua chave em uma requisição. Confira o resultado de cada identificador em removed, blocked ou invalid. Cobranças não removidas impedem a remoção do cliente, inclusive quando pagas. Identificadores repetidos são processados uma vez; clientes já removidos aparecem em removed. A remoção de clientes não remove cobranças nem devolve valores.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `identifiers` | `list<string\|null>` | Sim | IDs dos clientes que deseja remover. IDs repetidos não geram mais de uma remoção. IDs inválidos ou de clientes que a chave não pode acessar aparecem em invalid. |

Retorno: `200` `CustomerBatchDeleted`.

Aceita `idempotency_key` em `$options`.

## `disputes`

### `all`

Listar contestações.

```php
$raims->disputes->all($params, $options);
```

`GET /v1/disputes` · `ListDisputes`

Consulta as contestações da empresa e do ambiente da chave, com filtros e paginação por cursor.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de contestações por página. Valores inválidos usam o padrão; valores acima do máximo usam o limite. |
| `after` | `string` | Não | Cursor de page.next_after da resposta anterior. Preserve os filtros e a ordenação; não envie junto de before. |
| `before` | `string` | Não | Cursor de page.previous_before para voltar. Preserve os filtros e a ordenação; não envie junto de after. |
| `stage` | `string` | Não | Etapas do caso separadas por vírgula: open, in_analysis, refund_in_analysis, closed, cancelled, rejected ou failed. Closed informa análise encerrada, sem confirmar decisão favorável ou devolução. |
| `charge_id` | `string` | Não | Filtra pela cobrança vinculada, usando o identificador público completo. Um ID válido sem casos acessíveis retorna uma lista vazia. |
| `disputed_amount_cents` | `int` | Não | Filtra pelo valor contestado exato, em centavos de real. Não representa o valor atualmente bloqueado. |
| `created_gte` | `int` | Não | Inclui contestações criadas a partir deste instante, em segundos Unix UTC. O limite é inclusivo. |
| `created_lte` | `int` | Não | Inclui contestações criadas até este instante, em segundos Unix UTC. O limite é inclusivo e deve ser igual ou posterior a created_gte. |
| `sort_key` | `'created_at'\|'disputed_amount_cents'` | Não | Campo usado para ordenar a lista: criação ou valor contestado. |
| `sort_order` | `'asc'\|'desc'` | Não | Ordem crescente ou decrescente do campo selecionado. |

Retorno: `200` `DisputeListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar uma contestação.

```php
$raims->disputes->retrieve($id, $options);
```

`GET /v1/disputes/{id}` · `GetDispute`

Consulta a etapa, os motivos e os resultados disponíveis de uma contestação da empresa e do ambiente da chave.

`id`: Identificador completo da contestação retornado pela listagem, com prefixo dp_ ou med_.

Retorno: `200` `Dispute`.

Não aceita `idempotency_key`.

## `paymentLinks`

### `create`

Criar ou recuperar um link de pagamento.

```php
$raims->paymentLinks->create($params, $options);
```

`POST /v1/payment_links` · `CreatePaymentLink`

Cria o link de uma cobrança existente ou retorna o mesmo link quando ele já existe. Informe charge_id. Um link novo começa ativo; reutilizar um link pausado preserva a pausa. Título, valor, vencimento e cliente vêm da cobrança. Esta requisição não cria outra cobrança, não emite pagamentos e não solicita e-mail.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `charge_id` | `string` | Sim | Identificador da cobrança existente. O link utiliza o cliente, o valor e o vencimento dessa cobrança. |
| `success_redirect_url` | `string\|null` | Não | URL HTTPS de retorno após o pagamento confirmado. Omitir, enviar null ou texto vazio preserva a URL existente. |
| `cancel_redirect_url` | `string\|null` | Não | URL HTTPS para voltar ao seu site. Esse retorno não cancela a cobrança. Omitir, enviar null ou texto vazio preserva a URL existente. |
| `payment_method_types` | `list<1\|2>\|null` | Não | Meios permitidos no link. Omitir preserva a configuração; null remove a configuração própria. Uma lista limita os meios do checkout e não amplia os permitidos pela cobrança. |

Retorno: `200` `PaymentLink`, `201` `PaymentLink`.

Aceita `idempotency_key` em `$options`.

### `all`

Listar links de pagamento.

```php
$raims->paymentLinks->all($params, $options);
```

`GET /v1/payment_links` · `ListPaymentLinks`

Lista os links da empresa e do ambiente da chave, pela ordem decrescente das cobranças vinculadas. Use charge_id para localizar o link de uma cobrança e page.next_after para continuar.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de links por página. |
| `after` | `string` | Não | Cursor recebido em page.next_after. Mantenha o mesmo filtro charge_id. |
| `before` | `string` | Não | Cursor recebido em page.previous_before. Não envie junto com after. |
| `charge_id` | `string` | Não | Identificador da cobrança cujo link você quer consultar. |

Retorno: `200` `PaymentLinkListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um link de pagamento.

```php
$raims->paymentLinks->retrieve($id, $options);
```

`GET /v1/payment_links/{id}` · `GetPaymentLink`

Consulta a URL, a configuração e a disponibilidade atual do link. is_active informa se o link está ativo; is_payable indica se o checkout pode receber pagamento neste momento. O link precisa pertencer à empresa e ao ambiente da chave. Para valor, vencimento, cliente e status financeiro, use GET /v1/charges/{charge_id}.

`id`: Identificador público do link, recebido na criação ou na listagem.

Retorno: `200` `PaymentLinkDetails`.

Não aceita `idempotency_key`.

### `update`

Atualizar um link de pagamento.

```php
$raims->paymentLinks->update($id, $params, $options);
```

`PATCH /v1/payment_links/{id}` · `UpdatePaymentLink`

Atualiza as URLs de retorno, os meios de pagamento e a pausa do link. Envie is_active false para pausar e true para reativar a mesma URL; omitir preserva o estado. A pausa bloqueia novas ações no checkout e novos pedidos de envio por e-mail, sem cancelar Pix ou boleto emitidos nem prorrogar o vencimento. A cobrança precisa estar aberta e dentro do vencimento. Use clear_success_redirect_url ou clear_cancel_redirect_url com true para remover o respectivo destino.

`id`: Identificador público do link, recebido na criação ou na listagem.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `is_active` | `bool` | Não | Envie false para pausar o checkout e true para reativar a mesma URL. Omitir preserva o estado atual. A pausa não cancela Pix ou boleto emitidos nem prorroga o vencimento. |
| `success_redirect_url` | `string\|null` | Não | URL HTTPS de retorno após o pagamento confirmado. Omitir, enviar null ou texto vazio preserva a URL existente. Para removê-la, use clear_success_redirect_url com true, sem informar uma URL preenchida. |
| `cancel_redirect_url` | `string\|null` | Não | URL HTTPS para voltar ao seu site. Esse retorno não cancela a cobrança. Omitir, enviar null ou texto vazio preserva a URL existente. Para removê-la, use clear_cancel_redirect_url com true, sem informar uma URL preenchida. |
| `clear_success_redirect_url` | `bool` | Não | Envie true para remover o destino de retorno após o pagamento. False ou omissão não solicita remoção. Não envie true junto de success_redirect_url preenchida. |
| `clear_cancel_redirect_url` | `bool` | Não | Envie true para remover o destino de voltar ao site. False ou omissão não solicita remoção. Não envie true junto de cancel_redirect_url preenchida. |
| `payment_method_types` | `list<1\|2>\|null` | Não | Meios permitidos no link. Omitir preserva a configuração; null remove a configuração própria. Uma lista limita os meios do checkout e não amplia os permitidos pela cobrança. |

Retorno: `200` `PaymentLink`.

Aceita `idempotency_key` em `$options`.

### `sendEmail`

Enviar link por e-mail.

```php
$raims->paymentLinks->sendEmail($id, $options);
```

`POST /v1/payment_links/{id}/send_email` · `SendPaymentLinkEmail`

Solicita o envio ou reenvio do link ao destinatário da cobrança, sem criar outra cobrança ou alterar o checkout. A cobrança precisa estar aberta e dentro do vencimento. O intervalo mínimo de 60 segundos entre solicitações aceitas é compartilhado com a ação de reenvio do Dashboard. A resposta confirma o pedido de envio, não a entrega do e-mail. Sandbox também pode enviar e-mails reais. O link precisa estar ativo; a pausa impede o envio.

`id`: Identificador público do link a enviar, recebido na criação ou na listagem.

Retorno: `202` `PaymentLinkEmailRequestAccepted`.

Aceita `idempotency_key` em `$options`.

## `refunds`

### `all`

Listar estornos.

```php
$raims->refunds->all($params, $options);
```

`GET /v1/refunds` · `ListRefunds`

Lista as solicitações de estorno das cobranças da empresa e do ambiente da chave, das mais recentes para as mais antigas. Use a paginação para conciliar as devoluções sem consultar cada cobrança. A consulta não inicia nem repete estornos.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de estornos por página. Valores acima do máximo usam o máximo; ausência, vazio, texto, zero ou valor negativo usa o padrão. |
| `after` | `string` | Não | Cursor recebido em page.next_after para avançar nesta lista. Copie o valor completo. Não use um cursor da lista de uma cobrança nem envie junto de before. |
| `before` | `string` | Não | Cursor recebido em page.previous_before para voltar nesta lista. Copie o valor completo. Não use um cursor da lista de uma cobrança nem envie junto de after. |
| `created_gte` | `int` | Não | Filtra estornos solicitados a partir deste instante, inclusive, em segundos Unix UTC. Usa a data de criação da solicitação, sem depender da conclusão. Mantenha o valor ao mudar de página. |
| `created_lte` | `int` | Não | Filtra estornos solicitados até este instante, inclusive, em segundos Unix UTC. Se informar created_gte, este valor deve ser maior ou igual a ele. |

Retorno: `200` `RefundListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um estorno.

```php
$raims->refunds->retrieve($id, $options);
```

`GET /v1/refunds/{id}` · `GetRefund`

Consulta o estado, o valor, as datas e o motivo disponível de uma falha confirmada no estorno. A consulta não inicia nem repete a devolução.

`id`: Identificador do estorno recebido na solicitação ou no histórico de devoluções.

Retorno: `200` `Refund`.

Não aceita `idempotency_key`.

### `retrieveReceipt`

Baixar comprovante de estorno.

```php
$raims->refunds->retrieveReceipt($id, $options);
```

`GET /v1/refunds/{id}/receipt` · `GetRefundReceipt`

Obtenha uma URL temporária para baixar o comprovante em PDF de um estorno concluído. O documento informa o ID, o valor e a data de conclusão desse estorno. Para cobranças com mais de uma devolução, consulte cada ID desejado. A solicitação precisa estar em completed, com a data de conclusão registrada. A requisição exige a chave de API da mesma empresa e ambiente. O documento é emitido pela Raims e não substitui nota fiscal nem comprovante bancário.

`id`: Identificador público do estorno, recebido na solicitação ou na listagem de estornos.

Retorno: `200` `FinancialDocument`.

Não aceita `idempotency_key`.

## `statements`

### `all`

Recuperar extrato.

```php
$raims->statements->all($params, $options);
```

`GET /v1/statements` · `ListStatements`

Consulte os lançamentos do extrato da empresa e do ambiente da sua chave de API. Filtre por período, tipo de lançamento ou referência e percorra os resultados com os cursores retornados. Valores positivos representam entradas ou liberações; valores negativos representam saídas, tarifas ou bloqueios. Uma confirmação financeira pode levar alguns instantes para aparecer no extrato.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de lançamentos por página. |
| `after` | `string` | Não | Cursor recebido em page.next_after para avançar. Mantenha os mesmos filtros e a mesma ordem. Use somente after ou before. |
| `before` | `string` | Não | Cursor recebido em page.previous_before para voltar. Mantenha os mesmos filtros e a mesma ordem. Use somente after ou before. |
| `statement_type` | `string` | Não | Tipos de lançamento separados por vírgula: 1 recebimento; 2 tarifa; 3 tarifa de serviço; 4 saque; 5 devolução; 6 devolução parcial; 7 crédito por ajuste; 8 débito por ajuste; 9 bloqueio; 10 liberação. |
| `reference_id` | `string` | Não | Identificador da cobrança, contestação, saque ou ajuste relacionado ao lançamento. Use o ID completo retornado pela Raims. |
| `created_gte` | `int` | Não | Início inclusivo do período, em segundos Unix UTC. O filtro considera o instante do lançamento. |
| `created_lte` | `int` | Não | Fim inclusivo do período, em segundos Unix UTC. O instante informado é o limite exato; frações posteriores ao segundo informado ficam fora do período. |
| `sort_order` | `'asc'\|'desc'` | Não | Ordem dos lançamentos pela data: desc mostra os mais recentes primeiro; asc mostra os mais antigos primeiro. |

Retorno: `200` `StatementListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um lançamento.

```php
$raims->statements->retrieve($id, $options);
```

`GET /v1/statements/{id}` · `GetStatement`

Consulta um lançamento do extrato pelo ID, com seus valores e vínculos disponíveis, na empresa e no ambiente da chave de API.

`id`: Identificador do lançamento retornado na consulta do extrato.

Retorno: `200` `Statement`.

Não aceita `idempotency_key`.

## `webhookEventTypes`

### `all`

Listar os eventos disponíveis.

```php
$raims->webhookEventTypes->all($options);
```

`GET /v1/webhook_event_types` · `ListWebhookEventTypes`

Consulte os códigos que podem ser selecionados ao cadastrar ou atualizar um webhook. O teste técnico webhook.test é solicitado no endpoint de teste e não entra na lista de eventos do cadastro.

Retorno: `200` `WebhookEventTypeListResponse`.

Não aceita `idempotency_key`.

## `webhooks`

### `create`

Criar um webhook.

```php
$raims->webhooks->create($params, $options);
```

`POST /v1/webhooks` · `CreateWebhook`

Cadastre o endereço HTTPS que receberá os eventos escolhidos da empresa e do ambiente da sua chave de API. Guarde o id para administrar o destino e o secret_key para validar as assinaturas. As consultas do cadastro não retornam o segredo.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `name` | `string` | Sim | Nome para identificar o destino. |
| `url` | `string` | Sim | URL HTTPS que recebe os eventos. Use a porta padrão 443 e um endereço público externo à Raims. |
| `events` | `list<'charge.created'\|'charge.paid'\|'charge.canceled'\|'charge.failed'\|'refund.requested'\|'refund.completed'\|'refund.failed'>` | Sim | Códigos dos eventos que este destino deve receber. Consulte o catálogo de eventos disponíveis. |
| `is_active` | `bool` | Não | Define se o destino pode receber eventos após o cadastro. |

Retorno: `201` `WebhookCreated`.

Aceita `idempotency_key` em `$options`.

### `all`

Listar webhooks.

```php
$raims->webhooks->all($params, $options);
```

`GET /v1/webhooks` · `ListWebhooks`

Consulte os destinos cadastrados na empresa e no ambiente da sua chave de API, incluindo os desativados. Para avançar, copie page.next_after para after. A listagem não retorna segredos de assinatura.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de registros por página. A resposta informa o tamanho efetivo em page.page_size. |
| `after` | `string` | Não | Para avançar, copie page.next_after da resposta anterior. Use apenas after ou before. |
| `before` | `string` | Não | Para voltar, copie page.previous_before da resposta anterior. Use apenas after ou before. |

Retorno: `200` `WebhookListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um webhook.

```php
$raims->webhooks->retrieve($webhookId, $options);
```

`GET /v1/webhooks/{webhook_id}` · `GetWebhook`

Consulte a URL, os eventos assinados e a situação de um webhook. O segredo de assinatura não é retornado nesta consulta.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

Retorno: `200` `Webhook`.

Não aceita `idempotency_key`.

### `update`

Atualizar um webhook.

```php
$raims->webhooks->update($webhookId, $params, $options);
```

`PATCH /v1/webhooks/{webhook_id}` · `UpdateWebhook`

Altere o nome, a URL, a seleção de eventos ou a ativação de um webhook. Envie apenas os campos que deseja mudar; omitir um campo preserva seu valor. A seleção de eventos enviada substitui a anterior. Os campos não aceitam null nem valores vazios.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `name` | `string` | Não | Novo nome do webhook. |
| `url` | `string` | Não | Nova URL HTTPS que recebe os eventos, na porta padrão 443. |
| `events` | `list<string>` | Não | Lista completa dos eventos que o destino deve receber. Ela substitui a seleção anterior. |
| `is_active` | `bool` | Não | Use true para permitir envios ou false para desativar o destino. |

Retorno: `200` `Webhook`.

Aceita `idempotency_key` em `$options`.

### `delete`

Excluir um webhook.

```php
$raims->webhooks->delete($webhookId, $options);
```

`DELETE /v1/webhooks/{webhook_id}` · `DeleteWebhook`

Exclua um destino que não será mais usado. A exclusão impede novos envios, mas uma requisição já autorizada ou em andamento pode terminar. O histórico disponível continua consultável, inclusive pelo webhook_id excluído.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

Retorno: `204` `null`.

Aceita `idempotency_key` em `$options`.

### `retrieveHealth`

Consultar as falhas de um webhook.

```php
$raims->webhooks->retrieveHealth($webhookId, $options);
```

`GET /v1/webhooks/{webhook_id}/health` · `GetWebhookHealth`

Consulte a situação conhecida do destino e há quanto tempo suas entregas estão falhando. Os dados refletem os resultados de envio disponíveis e podem levar um tempo para aparecer após uma tentativa. A consulta não faz uma chamada ao destino. O estado healthy indica um sucesso conhecido; não garante que o endereço continua disponível. O estado unknown indica que ainda não há dados suficientes para informar a situação.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

Retorno: `200` `WebhookHealth`.

Não aceita `idempotency_key`.

### `setPause`

Pausar ou retomar envios.

```php
$raims->webhooks->setPause($webhookId, $params, $options);
```

`PATCH /v1/webhooks/{webhook_id}/pause` · `SetWebhookPause`

Pausa os envios de um webhook ativo sem desativá-lo. Eventos novos e entregas pendentes aguardam a retomada enquanto estiverem dentro de seu prazo original. A pausa não amplia esse prazo: 72 horas em Produção e 4 horas no Sandbox. Envie is_paused false para retomar as entregas ainda válidas. A retomada respeita os limites de envio, sem garantir ordem ou horário. O envio explícito de um evento de teste continua disponível durante a pausa.

`webhook_id`: Identificador do webhook que terá os envios pausados ou retomados.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `is_paused` | `bool` | Sim | Envie true para pausar os envios ou false para retomá-los. Repetir o mesmo valor preserva o estado e a data de pausa. |

Retorno: `200` `WebhookPause`.

Aceita `idempotency_key` em `$options`.

### `rotateSecret`

Rotacionar o segredo de assinatura.

```php
$raims->webhooks->rotateSecret($webhookId, $params, $options);
```

`POST /v1/webhooks/{webhook_id}/rotate_secret` · `RotateWebhookSecret`

Substitua o segredo usado para assinar os eventos, preservando o identificador e a configuração do webhook. Guarde o secret_key retornado e atualize o receptor durante o período de transição. Repetir uma rotação com período positivo enquanto a transição está ativa retorna o mesmo segredo e o mesmo vencimento. Para revogar os segredos anteriores, envie grace_period_seconds igual a zero: uma nova assinatura será gerada, inclusive durante uma transição ativa. Requisições já autorizadas ou em andamento podem chegar com a assinatura anterior.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `grace_period_seconds` | `int` | Não | Período em que o segredo anterior pode continuar assinando envios. Zero gera uma nova assinatura e encerra a validade dos segredos anteriores, inclusive durante uma transição ativa. Requisições já autorizadas ou em andamento podem chegar com a assinatura anterior. |

Retorno: `200` `WebhookSecretRotation`.

Aceita `idempotency_key` em `$options`.

### `test`

Enviar um evento de teste.

```php
$raims->webhooks->test($webhookId, $options);
```

`POST /v1/webhooks/{webhook_id}/test` · `TestWebhook`

Envie webhook.test para conferir o recebimento e a validação da assinatura de um webhook ativo. A requisição não recebe corpo. O teste faz uma tentativa HTTP em até cinco minutos e não altera cobranças, saldo ou a contagem de falhas do destino. O aceite do pedido não confirma a entrega. Confira o recebimento nos registros do receptor.

`webhook_id`: Identificador do webhook, recebido no cadastro ou na listagem.

Retorno: `202` `WebhookTestAccepted`.

Aceita `idempotency_key` em `$options`.

## `withdrawalDestinations`

### `all`

Listar destinos de saque.

```php
$raims->withdrawalDestinations->all($params, $options);
```

`GET /v1/withdrawal_destinations` · `ListWithdrawalDestinations`

Consulte os destinos Pix próprios verificados e identifique o padrão antes de solicitar um saque. A consulta exige uma chave de Produção.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de registros considerados para a página. A lista pode ter menos destinos e ainda indicar uma próxima página; continue enquanto page.has_next for true. |
| `after` | `string` | Não | Cursor recebido em page.next_after para avançar. Copie o valor inteiro e mantenha a mesma chave de API. Não envie junto de before. |
| `before` | `string` | Não | Cursor recebido em page.previous_before para voltar. Copie o valor inteiro e mantenha a mesma chave de API. Não envie junto de after. |

Retorno: `200` `WithdrawalDestinationListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um destino de saque.

```php
$raims->withdrawalDestinations->retrieve($id, $options);
```

`GET /v1/withdrawal_destinations/{id}` · `GetWithdrawalDestination`

Consulte um destino Pix próprio pelo identificador recebido na listagem. A resposta apresenta a chave mascarada e informa se ela é o padrão atual. Use uma chave de Produção.

`id`: Identificador do destino retornado pela listagem. Use o ID completo com prefixo wdst_; o ID de saque wd_ pertence a outro recurso.

Retorno: `200` `WithdrawalDestination`.

Não aceita `idempotency_key`.

## `withdrawals`

### `create`

Solicitar saque.

```php
$raims->withdrawals->create($params, $options);
```

`POST /v1/withdrawals` · `CreateWithdrawal`

Solicite um saque Pix para a chave padrão da própria empresa cadastrada no Dashboard. O valor solicitado inclui a tarifa. Guarde o identificador e acompanhe o mesmo saque enquanto o resultado estiver pendente.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Sim | Valor total a retirar, em centavos de real, incluindo a tarifa. Consulte as condições de saque para conferir saldo, tarifa e limites antes de solicitar. |

Retorno: `200` `Withdrawal`, `202` `Withdrawal`.

Aceita `idempotency_key` em `$options`.

### `all`

Listar saques.

```php
$raims->withdrawals->all($params, $options);
```

`GET /v1/withdrawals` · `ListWithdrawals`

Consulte o histórico de saques da sua empresa. Use os filtros de status e período para acompanhar as transferências e conferir as saídas do extrato. A consulta considera o ambiente da sua chave de API.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `page_size` | `int` | Não | Quantidade de saques por página. |
| `after` | `string` | Não | Cursor next_after retornado na página anterior. Mantenha os mesmos filtros e a mesma ordem da consulta. |
| `before` | `string` | Não | Cursor previous_before retornado na página seguinte. Mantenha os mesmos filtros e a mesma ordem da consulta. |
| `status` | `string` | Não | Filtre por um ou mais estados separados por vírgula: PENDING, PROCESSING, COMPLETED, FAILED ou CANCELED. |
| `requested_gte` | `int` | Não | Retorne saques solicitados a partir deste instante, inclusive. Informe segundos Unix UTC. |
| `requested_lte` | `int` | Não | Retorne saques solicitados até este instante, inclusive. Informe segundos Unix UTC. |
| `sort_order` | `'asc'\|'desc'` | Não | Ordenação pela data de solicitação: desc mostra os mais recentes primeiro; asc mostra os mais antigos primeiro. |

Retorno: `200` `WithdrawalListResponse`.

Não aceita `idempotency_key`.

### `retrieve`

Recuperar um saque.

```php
$raims->withdrawals->retrieve($id, $options);
```

`GET /v1/withdrawals/{id}` · `GetWithdrawal`

Consulte o status, os valores e o destino de um saque pelo identificador. Use o mesmo ID que aparece no histórico de saques ou na referência do extrato.

`id`: Identificador do saque, com prefixo wd_.

Retorno: `200` `Withdrawal`.

Não aceita `idempotency_key`.

### `retrieveLimits`

Consultar condições de saque.

```php
$raims->withdrawals->retrieveLimits($options);
```

`GET /v1/withdrawals/limits` · `GetWithdrawalLimits`

Consulte saldo disponível, mínimo, tarifa e limites de saque para planejar uma retirada. A consulta não reserva dinheiro nem limite e não garante que uma solicitação será aceita.

Retorno: `200` `WithdrawalLimits`.

Não aceita `idempotency_key`.

### `retrieveSummary`

Consultar saques em processamento.

```php
$raims->withdrawals->retrieveSummary($options);
```

`GET /v1/withdrawals/summary` · `GetWithdrawalSummary`

Consulte a quantidade e o valor total dos saques pendentes ou em processamento da empresa. A consulta exige uma chave de API de Produção e não altera o andamento dos pedidos.

Retorno: `200` `WithdrawalSummary`.

Não aceita `idempotency_key`.

### `retrieveAutomaticSettings`

Consultar saque automático.

```php
$raims->withdrawals->retrieveAutomaticSettings($options);
```

`GET /v1/withdrawals/automatic_settings` · `GetAutomaticWithdrawalSettings`

Consulte a ativação, o valor mínimo, o horário diário e a próxima tentativa prevista de saque da empresa. A consulta exige uma chave de API de Produção.

Retorno: `200` `AutomaticWithdrawalSettings`.

Não aceita `idempotency_key`.

### `updateAutomaticSettings`

Atualizar saque automático.

```php
$raims->withdrawals->updateAutomaticSettings($params, $options);
```

`PATCH /v1/withdrawals/automatic_settings` · `UpdateAutomaticWithdrawalSettings`

Ative, desative ou ajuste a tentativa diária de saque para o destino próprio selecionado. Use uma chave de API de Produção. Campos omitidos preservam o valor atual; salvar não solicita um saque imediato.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `enabled` | `bool` | Não | Ativa ou desativa novas tentativas de saque. Desativar não cancela saques já solicitados. |
| `minimum_amount_cents` | `int` | Não | Saldo mínimo para tentar o saque, em centavos de real. O valor retirado depende do saldo disponível e do limite Pix restante. |
| `time` | `string` | Não | Horário diário no formato HH:mm, pelo fuso de São Paulo. |

Retorno: `200` `AutomaticWithdrawalSettings`.

Aceita `idempotency_key` em `$options`.

## `simulations->charges`

### `confirmPayment`

Confirmar pagamento.

```php
$raims->simulations->charges->confirmPayment($id, $options);
```

`POST /v1/simulations/charges/{id}/confirm_payment` · `ConfirmSimulationPayment`

Confirme o pagamento de uma cobrança de teste e confira o crédito no saldo do Sandbox.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

Retorno: `200` `ChargeSimulation`.

Aceita `idempotency_key` em `$options`.

### `failPayment`

Simular falha no pagamento.

```php
$raims->simulations->charges->failPayment($id, $options);
```

`POST /v1/simulations/charges/{id}/fail_payment` · `FailSimulationPayment`

Simule a falha de uma cobrança aberta para testar o tratamento desse resultado no Sandbox.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

Retorno: `200` `ChargeSimulation`.

Aceita `idempotency_key` em `$options`.

### `configureScenario`

Preparar cenário.

```php
$raims->simulations->charges->configureScenario($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/scenario` · `ConfigureSimulationScenario`

Escolha uma pendência ou falha para a emissão de Pix ou boleto, ou para o cancelamento de uma cobrança no Sandbox. Depois, execute a ação sobre a mesma cobrança.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `action` | `'pix_issuance'\|'boleto_issuance'\|'cancellation'` | Sim | Ação a testar: pix_issuance para emissão Pix, boleto_issuance para emissão de boleto ou cancellation para cancelamento. Para cancellation, mantenha a cobrança aberta com Pix ou boleto já emitido, sem confirmar o pagamento. |
| `outcome` | `'pending'\|'failed'` | Sim | Resultado da ação: pending mantém a confirmação em aberto; failed apresenta uma falha. |

Retorno: `200` `ChargeScenario`.

Aceita `idempotency_key` em `$options`.

### `resolveScenario`

Resolver cenário pendente.

```php
$raims->simulations->charges->resolveScenario($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/resolve` · `ResolveSimulationScenario`

Conclua ou falhe uma emissão ou um cancelamento que ficou pendente no cenário de teste. A ação precisa ter sido iniciada sobre a cobrança.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `action` | `'pix_issuance'\|'boleto_issuance'\|'cancellation'` | Sim | Ação a testar: pix_issuance para emissão Pix, boleto_issuance para emissão de boleto ou cancellation para cancelamento. |
| `outcome` | `'completed'\|'failed'` | Sim | Resultado para a pendência: completed conclui a ação; failed confirma a falha. |

Retorno: `200` `ChargeScenario`.

Aceita `idempotency_key` em `$options`.

### `issuePayment`

Emitir pagamento de teste.

```php
$raims->simulations->charges->issuePayment($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/issue_payment` · `IssueSimulationPayment`

Emita Pix ou boleto de uma cobrança existente no Sandbox. Um cenário preparado para essa emissão define se ela ficará pendente ou apresentará falha.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `payment_method` | `int` | Sim | Meio a emitir: 1 para Pix ou 2 para boleto. Deve estar habilitado na cobrança. |

Retorno: `200` `Charge`, `202` `Charge`.

Aceita `idempotency_key` em `$options`.

### `compensateBoleto`

Simular compensação de boleto.

```php
$raims->simulations->charges->compensateBoleto($id, $options);
```

`POST /v1/simulations/charges/{id}/compensate_boleto` · `CompensateSimulationBoleto`

Registre um boleto como compensado, ainda aguardando liquidação. Esta etapa não credita o saldo nem disponibiliza recibo de pagamento concluído.

`id`: Identificador público da cobrança criada no Sandbox da sua empresa.

Retorno: `200` `ChargeBoletoCompensation`.

Aceita `idempotency_key` em `$options`.

### `createRefund`

Simular estorno pendente.

```php
$raims->simulations->charges->createRefund($id, $params, $options);
```

`POST /v1/simulations/charges/{id}/refunds` · `CreateSimulationRefund`

Inicie uma devolução Pix de teste que aguarda confirmação. Use o ID retornado para concluir o estorno ou simular uma falha.

`id`: Identificador da cobrança que recebeu o pagamento Pix.

| Campo em `$params` | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `amount_cents` | `int` | Sim | Valor solicitado para devolução, em centavos de real. Para devolver tudo, informe o valor restante mostrado na consulta de elegibilidade. |
| `description` | `string\|null` | Não | Descrição da solicitação de devolução. Você pode deixar este campo vazio. |

Retorno: `200` `Refund`, `202` `Refund`.

Aceita `idempotency_key` em `$options`.

## `simulations->refunds`

### `complete`

Concluir estorno simulado.

```php
$raims->simulations->refunds->complete($id, $options);
```

`POST /v1/simulations/refunds/{id}/complete` · `CompleteSimulationRefund`

Conclua um estorno Pix pendente no Sandbox. O valor não é descontado novamente do saldo.

`id`: Identificador do estorno de teste retornado por Simular estorno pendente.

Retorno: `200` `Refund`.

Aceita `idempotency_key` em `$options`.

### `fail`

Simular falha no estorno.

```php
$raims->simulations->refunds->fail($id, $options);
```

`POST /v1/simulations/refunds/{id}/fail` · `FailSimulationRefund`

Simule a falha de um estorno Pix pendente no Sandbox e confira a devolução do valor ao saldo disponível.

`id`: Identificador do estorno de teste retornado por Simular estorno pendente.

Retorno: `200` `Refund`.

Aceita `idempotency_key` em `$options`.

## Constantes de valores públicos

Use `Raims\EnumValues` para os valores nomeados do contrato. Os nomes identificam o schema ou endpoint e o campo de origem; `QUERY` identifica um filtro da URL.
Valores numéricos recebem nomes somente quando o contrato fornece rótulos públicos; os demais continuam documentados no campo.
As constantes não alteram os valores enviados à API.

```php
use Raims\EnumValues;

$params = ['payment_method' => EnumValues::CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX];
```

| Constante em `EnumValues` | Valor | Campo público no contrato |
| --- | --- | --- |
| `BALANCE_FORECAST_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `BalanceForecast.period` |
| `BALANCE_FORECAST_PERIOD_TODAY` | `'today'` | `BalanceForecast.period` |
| `CHARGE_CREATED_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `ChargeCreated.payment_method_types` |
| `CHARGE_CREATED_PAYMENT_METHOD_TYPES_PIX` | `1` | `ChargeCreated.payment_method_types` |
| `CHARGE_LIST_ITEM_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `ChargeListItem.payment_method_types` |
| `CHARGE_LIST_ITEM_PAYMENT_METHOD_TYPES_PIX` | `1` | `ChargeListItem.payment_method_types` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_AWAITING_PAYMENT` | `'awaiting_payment'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_COMPENSATED` | `'compensated'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_PAYMENT_STATUS_SETTLED` | `'settled'` | `ChargePaymentInformation.boleto_payment_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_AVAILABLE` | `'available'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_FAILED` | `'failed'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_NOT_GENERATED` | `'not_generated'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_BOLETO_STATUS_PENDING` | `'pending'` | `ChargePaymentInformation.boleto_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_AVAILABLE` | `'available'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_FAILED` | `'failed'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_NOT_GENERATED` | `'not_generated'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_INFORMATION_PIX_STATUS_PENDING` | `'pending'` | `ChargePaymentInformation.pix_status` |
| `CHARGE_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `Charge.payment_method_types` |
| `CHARGE_PAYMENT_METHOD_TYPES_PIX` | `1` | `Charge.payment_method_types` |
| `CHARGE_SCENARIO_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_ACTION_CANCELLATION` | `'cancellation'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ChargeScenario.action` |
| `CHARGE_SCENARIO_OUTCOME_COMPLETED` | `'completed'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_OUTCOME_FAILED` | `'failed'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_OUTCOME_PENDING` | `'pending'` | `ChargeScenario.outcome` |
| `CHARGE_SCENARIO_REQUEST_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_ACTION_CANCELLATION` | `'cancellation'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ChargeScenarioRequest.action` |
| `CHARGE_SCENARIO_REQUEST_OUTCOME_FAILED` | `'failed'` | `ChargeScenarioRequest.outcome` |
| `CHARGE_SCENARIO_REQUEST_OUTCOME_PENDING` | `'pending'` | `ChargeScenarioRequest.outcome` |
| `CHARGE_SCENARIO_STATE_COMPLETED` | `'completed'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_CONFIGURED` | `'configured'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_FAILED` | `'failed'` | `ChargeScenario.state` |
| `CHARGE_SCENARIO_STATE_PENDING` | `'pending'` | `ChargeScenario.state` |
| `CHARGE_STATISTICS_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `ChargeStatistics.period` |
| `CHARGE_STATISTICS_PERIOD_TODAY` | `'today'` | `ChargeStatistics.period` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_INVALID_IMAGE` | `'CHECKOUT_ASSET_INVALID_IMAGE'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_PROCESSING_FAILED` | `'CHECKOUT_ASSET_PROCESSING_FAILED'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_ERROR_CODE_CHECKOUT_ASSET_UPLOAD_NOT_FOUND` | `'CHECKOUT_ASSET_UPLOAD_NOT_FOUND'` | `CheckoutAsset.error_code` |
| `CHECKOUT_ASSET_STATUS_ABSENT` | `'absent'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_FAILED` | `'failed'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_PENDING` | `'pending'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_STATUS_READY` | `'ready'` | `CheckoutAsset.status` |
| `CHECKOUT_ASSET_UPLOAD_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CheckoutAssetUpload.content_type` |
| `CHECKOUT_ASSET_UPLOAD_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CheckoutAssetUpload.content_type` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_BOLETO` | `2` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_INDEFINIDO` | `0` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_PIX` | `1` | `CreateChargeRequest.payment_method` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `CreateChargeRequest.payment_method_types` |
| `CREATE_CHARGE_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `CreateChargeRequest.payment_method_types` |
| `CREATE_CHECKOUT_FAVICON_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CreateCheckoutFaviconUploadRequest.content_type` |
| `CREATE_CHECKOUT_FAVICON_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CreateCheckoutFaviconUploadRequest.content_type` |
| `CREATE_CHECKOUT_LOGO_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_JPEG` | `'image/jpeg'` | `CreateCheckoutLogoUploadRequest.content_type` |
| `CREATE_CHECKOUT_LOGO_UPLOAD_REQUEST_CONTENT_TYPE_IMAGE_PNG` | `'image/png'` | `CreateCheckoutLogoUploadRequest.content_type` |
| `CREATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `CreatePaymentLinkRequest.payment_method_types` |
| `CREATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `CreatePaymentLinkRequest.payment_method_types` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_CANCELED` | `'charge.canceled'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_CREATED` | `'charge.created'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_FAILED` | `'charge.failed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_CHARGE_PAID` | `'charge.paid'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_COMPLETED` | `'refund.completed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_FAILED` | `'refund.failed'` | `CreateWebhookRequest.events` |
| `CREATE_WEBHOOK_REQUEST_EVENTS_REFUND_REQUESTED` | `'refund.requested'` | `CreateWebhookRequest.events` |
| `CUSTOMER_CREATED_TAX_ID_TYPE_CNPJ` | `'cnpj'` | `CustomerCreated.tax_id_type` |
| `CUSTOMER_CREATED_TAX_ID_TYPE_CPF` | `'cpf'` | `CustomerCreated.tax_id_type` |
| `CUSTOMER_LIST_ITEM_TAX_ID_TYPE_CNPJ` | `'cnpj'` | `CustomerListItem.tax_id_type` |
| `CUSTOMER_LIST_ITEM_TAX_ID_TYPE_CPF` | `'cpf'` | `CustomerListItem.tax_id_type` |
| `DISPUTE_INFRACTION_RESULT_AGREED` | `'agreed'` | `Dispute.infraction_result` |
| `DISPUTE_INFRACTION_RESULT_DISAGREED` | `'disagreed'` | `Dispute.infraction_result` |
| `DISPUTE_KIND_CHARGEBACK` | `'chargeback'` | `Dispute.kind` |
| `DISPUTE_KIND_MED` | `'med'` | `Dispute.kind` |
| `DISPUTE_LIST_ITEM_KIND_CHARGEBACK` | `'chargeback'` | `DisputeListItem.kind` |
| `DISPUTE_LIST_ITEM_KIND_MED` | `'med'` | `DisputeListItem.kind` |
| `DISPUTE_LIST_ITEM_STAGE_CANCELLED` | `'cancelled'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_CLOSED` | `'closed'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_FAILED` | `'failed'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_IN_ANALYSIS` | `'in_analysis'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_OPEN` | `'open'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_REFUND_IN_ANALYSIS` | `'refund_in_analysis'` | `DisputeListItem.stage` |
| `DISPUTE_LIST_ITEM_STAGE_REJECTED` | `'rejected'` | `DisputeListItem.stage` |
| `DISPUTE_REFUND_RESULT_PARTIALLY_ACCEPTED` | `'partially_accepted'` | `Dispute.refund_result` |
| `DISPUTE_REFUND_RESULT_REJECTED` | `'rejected'` | `Dispute.refund_result` |
| `DISPUTE_REFUND_RESULT_TOTALLY_ACCEPTED` | `'totally_accepted'` | `Dispute.refund_result` |
| `DISPUTE_STAGE_CANCELLED` | `'cancelled'` | `Dispute.stage` |
| `DISPUTE_STAGE_CLOSED` | `'closed'` | `Dispute.stage` |
| `DISPUTE_STAGE_FAILED` | `'failed'` | `Dispute.stage` |
| `DISPUTE_STAGE_IN_ANALYSIS` | `'in_analysis'` | `Dispute.stage` |
| `DISPUTE_STAGE_OPEN` | `'open'` | `Dispute.stage` |
| `DISPUTE_STAGE_REFUND_IN_ANALYSIS` | `'refund_in_analysis'` | `Dispute.stage` |
| `DISPUTE_STAGE_REJECTED` | `'rejected'` | `Dispute.stage` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `GetBalanceForecast.query.period` |
| `GET_BALANCE_FORECAST_QUERY_PERIOD_TODAY` | `'today'` | `GetBalanceForecast.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_15_DAYS` | `'last_15_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_30_DAYS` | `'last_30_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_LAST_7_DAYS` | `'last_7_days'` | `GetChargeStatistics.query.period` |
| `GET_CHARGE_STATISTICS_QUERY_PERIOD_TODAY` | `'today'` | `GetChargeStatistics.query.period` |
| `LIST_CHARGES_QUERY_SORT_KEY_AMOUNT_CENTS` | `'amount_cents'` | `ListCharges.query.sort_key` |
| `LIST_CHARGES_QUERY_SORT_KEY_CREATED_AT` | `'created_at'` | `ListCharges.query.sort_key` |
| `LIST_CHARGES_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListCharges.query.sort_order` |
| `LIST_CHARGES_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListCharges.query.sort_order` |
| `LIST_DISPUTES_QUERY_SORT_KEY_CREATED_AT` | `'created_at'` | `ListDisputes.query.sort_key` |
| `LIST_DISPUTES_QUERY_SORT_KEY_DISPUTED_AMOUNT_CENTS` | `'disputed_amount_cents'` | `ListDisputes.query.sort_key` |
| `LIST_DISPUTES_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListDisputes.query.sort_order` |
| `LIST_DISPUTES_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListDisputes.query.sort_order` |
| `LIST_STATEMENTS_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListStatements.query.sort_order` |
| `LIST_STATEMENTS_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListStatements.query.sort_order` |
| `LIST_WITHDRAWALS_QUERY_SORT_ORDER_ASC` | `'asc'` | `ListWithdrawals.query.sort_order` |
| `LIST_WITHDRAWALS_QUERY_SORT_ORDER_DESC` | `'desc'` | `ListWithdrawals.query.sort_order` |
| `PAYMENT_LINK_DETAILS_EFFECTIVE_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLinkDetails.effective_payment_method_types` |
| `PAYMENT_LINK_DETAILS_EFFECTIVE_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLinkDetails.effective_payment_method_types` |
| `PAYMENT_LINK_DETAILS_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLinkDetails.payment_method_types` |
| `PAYMENT_LINK_DETAILS_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLinkDetails.payment_method_types` |
| `PAYMENT_LINK_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `PaymentLink.payment_method_types` |
| `PAYMENT_LINK_PAYMENT_METHOD_TYPES_PIX` | `1` | `PaymentLink.payment_method_types` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_BOLETO_AWAITING_SETTLEMENT` | `'BOLETO_AWAITING_SETTLEMENT'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_CHARGE_EXPIRED` | `'CHARGE_EXPIRED'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_CHARGE_NOT_OPEN` | `'CHARGE_NOT_OPEN'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PAYMENT_LINK_PAUSED` | `'PAYMENT_LINK_PAUSED'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PAYMENT_METHODS_UNAVAILABLE` | `'PAYMENT_METHODS_UNAVAILABLE'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PIX_CANCELLATION_PENDING` | `'PIX_CANCELLATION_PENDING'` | `PaymentLinkUnavailableReason.code` |
| `PAYMENT_LINK_UNAVAILABLE_REASON_CODE_PIX_CANCELLED` | `'PIX_CANCELLED'` | `PaymentLinkUnavailableReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_FAILURE_REASON_UNAVAILABLE` | `'REFUND_FAILURE_REASON_UNAVAILABLE'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_NOT_SUBMITTED` | `'REFUND_NOT_SUBMITTED'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_PROCESSING_FAILED` | `'REFUND_PROCESSING_FAILED'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_PROCESSING_UNAVAILABLE` | `'REFUND_PROCESSING_UNAVAILABLE'` | `RefundFailureReason.code` |
| `REFUND_FAILURE_REASON_CODE_REFUND_REJECTED` | `'REFUND_REJECTED'` | `RefundFailureReason.code` |
| `REFUND_STATUS_ACCEPTED` | `'accepted'` | `Refund.status` |
| `REFUND_STATUS_COMPLETED` | `'completed'` | `Refund.status` |
| `REFUND_STATUS_FAILED` | `'failed'` | `Refund.status` |
| `REFUND_STATUS_RESERVED` | `'reserved'` | `Refund.status` |
| `REFUND_STATUS_UNKNOWN` | `'unknown'` | `Refund.status` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_ALREADY_COMPLETED` | `'PIX_REFUND_ALREADY_COMPLETED'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_CASHIN_NOT_FOUND` | `'PIX_REFUND_CASHIN_NOT_FOUND'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_INSUFFICIENT_BALANCE` | `'PIX_REFUND_INSUFFICIENT_BALANCE'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_IN_PROGRESS` | `'PIX_REFUND_IN_PROGRESS'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_NOT_AVAILABLE` | `'PIX_REFUND_NOT_AVAILABLE'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_PROVIDER_ACCOUNT_NOT_READY` | `'PIX_REFUND_PROVIDER_ACCOUNT_NOT_READY'` | `RefundUnavailableReason.code` |
| `REFUND_UNAVAILABLE_REASON_CODE_PIX_REFUND_PROVIDER_UNAVAILABLE` | `'PIX_REFUND_PROVIDER_UNAVAILABLE'` | `RefundUnavailableReason.code` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_BOLETO_ISSUANCE` | `'boleto_issuance'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_CANCELLATION` | `'cancellation'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_ACTION_PIX_ISSUANCE` | `'pix_issuance'` | `ResolveChargeScenarioRequest.action` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_OUTCOME_COMPLETED` | `'completed'` | `ResolveChargeScenarioRequest.outcome` |
| `RESOLVE_CHARGE_SCENARIO_REQUEST_OUTCOME_FAILED` | `'failed'` | `ResolveChargeScenarioRequest.outcome` |
| `STATEMENT_REFERENCE_TYPE_AJUSTE` | `4` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_COBRANCA` | `1` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_CONTESTACAO` | `2` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_MANUAL` | `5` | `Statement.reference_type` |
| `STATEMENT_REFERENCE_TYPE_SAQUE` | `3` | `Statement.reference_type` |
| `STATEMENT_STATEMENT_TYPE_BLOQUEIO` | `9` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_CREDITO_POR_AJUSTE` | `7` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEBITO_POR_AJUSTE` | `8` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEVOLUCAO` | `5` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_DEVOLUCAO_PARCIAL` | `6` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_LIBERACAO` | `10` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_RECEBIMENTO` | `1` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_SAQUE` | `4` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_TARIFA` | `2` | `Statement.statement_type` |
| `STATEMENT_STATEMENT_TYPE_TARIFA_DE_SERVICO` | `3` | `Statement.statement_type` |
| `UPDATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_BOLETO` | `2` | `UpdatePaymentLinkRequest.payment_method_types` |
| `UPDATE_PAYMENT_LINK_REQUEST_PAYMENT_METHOD_TYPES_PIX` | `1` | `UpdatePaymentLinkRequest.payment_method_types` |
| `WEBHOOK_CREATED_DISABLED_REASON_CONTINUOUS_FAILURE` | `'continuous_failure'` | `WebhookCreated.disabled_reason` |
| `WEBHOOK_CREATED_DISABLED_REASON_MANUAL` | `'manual'` | `WebhookCreated.disabled_reason` |
| `WEBHOOK_DISABLED_REASON_CONTINUOUS_FAILURE` | `'continuous_failure'` | `Webhook.disabled_reason` |
| `WEBHOOK_DISABLED_REASON_MANUAL` | `'manual'` | `Webhook.disabled_reason` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_CANCELED` | `'charge.canceled'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_CREATED` | `'charge.created'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_FAILED` | `'charge.failed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_CHARGE_PAID` | `'charge.paid'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_COMPLETED` | `'refund.completed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_FAILED` | `'refund.failed'` | `WebhookEventType.code` |
| `WEBHOOK_EVENT_TYPE_CODE_REFUND_REQUESTED` | `'refund.requested'` | `WebhookEventType.code` |
| `WEBHOOK_HEALTH_STATUS_DISABLED` | `'disabled'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_FAILING` | `'failing'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_HEALTHY` | `'healthy'` | `WebhookHealth.status` |
| `WEBHOOK_HEALTH_STATUS_UNKNOWN` | `'unknown'` | `WebhookHealth.status` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_CNPJ` | `'CNPJ'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_EMAIL` | `'EMAIL'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_EVP` | `'EVP'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_DESTINATION_KEY_TYPE_PHONE` | `'PHONE'` | `WithdrawalDestination.key_type` |
| `WITHDRAWAL_FAILURE_REASON_CODE_ABOVE_MAXIMUM_WITHDRAWAL` | `'ABOVE_MAXIMUM_WITHDRAWAL'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_ACCOUNT_UNAVAILABLE` | `'BANK_ACCOUNT_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_PROCESSING_FAILURE` | `'BANK_PROCESSING_FAILURE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_TIMEOUT` | `'BANK_TIMEOUT'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BANK_UNSUPPORTED` | `'BANK_UNSUPPORTED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BELOW_MINIMUM_WITHDRAWAL` | `'BELOW_MINIMUM_WITHDRAWAL'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_BLOCKED_BALANCE` | `'BLOCKED_BALANCE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_DUPLICATED_REQUEST` | `'DUPLICATED_REQUEST'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INSUFFICIENT_BALANCE` | `'INSUFFICIENT_BALANCE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INTERNAL_ERROR` | `'INTERNAL_ERROR'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INVALID_ACCOUNT_TYPE` | `'INVALID_ACCOUNT_TYPE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_INVALID_BANK_ACCOUNT` | `'INVALID_BANK_ACCOUNT'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_RISK_POLICY_REJECTED` | `'RISK_POLICY_REJECTED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_FAILURE_REASON_UNAVAILABLE` | `'WITHDRAWAL_FAILURE_REASON_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_LIMIT_REACHED` | `'WITHDRAWAL_LIMIT_REACHED'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_FAILURE_REASON_CODE_WITHDRAWAL_TEMPORARILY_UNAVAILABLE` | `'WITHDRAWAL_TEMPORARILY_UNAVAILABLE'` | `WithdrawalFailureReason.code` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_CNPJ` | `'CNPJ'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_EMAIL` | `'EMAIL'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_EVP` | `'EVP'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_PIX_DESTINATION_KEY_TYPE_PHONE` | `'PHONE'` | `WithdrawalPixDestination.key_type` |
| `WITHDRAWAL_STATUS_CANCELED` | `'CANCELED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_COMPLETED` | `'COMPLETED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_FAILED` | `'FAILED'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_PENDING` | `'PENDING'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_PROCESSING` | `'PROCESSING'` | `Withdrawal.status` |
| `WITHDRAWAL_STATUS_UNKNOWN` | `'UNKNOWN'` | `Withdrawal.status` |
