# Contrato e manutenção

## Fonte da biblioteca

O SDK consome o OpenAPI da API pública, distribuído em
[raimshq/openapi](https://github.com/raimshq/openapi). O snapshot em
`contracts/openapi.json` contém somente a superfície publicada. O arquivo
`contracts/source.json` identifica a revisão imutável, a URL de origem, o SHA-256,
a versão da API e a quantidade de endpoints.

A revisão inicial fixa 67 endpoints e gera 77 modelos de resposta. O gerador
não baixa arquivos, não chama a API e não acessa credenciais.

## Organização

| Diretório ou arquivo | Responsabilidade |
| --- | --- |
| `src/RaimsClient.php` | Composição dos serviços e configuração isolada |
| `src/Service` | Métodos nomeados para os endpoints |
| `src/Generated/Contract.php` | Método, caminho, parâmetros e retornos por endpoint |
| `src/EnumValues.php` | Constantes geradas dos enums públicos |
| `src/Model` | Classes de resposta e tipos de objetos aninhados |
| `src/ApiRequestor.php` | Serialização, execução, retries de leitura e erros |
| `src/HttpClient` | Interface de transporte, cURL e resposta HTTP |
| `src/ApiResource.php` | Recursos somente leitura e serialização |
| `src/Collection.php` | Navegação por cursor |
| `src/ResponseValidator.php` | Conferência estrutural das respostas pelos metadados gerados |
| `src/JsonNumberNormalizer.php` | Leitura exata das representações JSON de inteiros antes da decodificação |
| `src/RequestDiagnostics.php` | Metadados de uma tentativa HTTP, sem parâmetros ou credenciais |
| `src/Exception` | Categorias de falha e metadados |
| `src/Webhook.php` | Autenticação do corpo de uma entrega |
| `scripts/generate.php` | Geração determinística offline |
| `docs/reference.md` | Referência gerada da superfície PHP |

`AbstractService.php` e `SimulationsService.php` são mantidos manualmente.
Os demais serviços, modelos, constantes, catálogo de endpoints e referência são gerados.
Edite o mapa de métodos no gerador quando acrescentar um recurso; a geração
recusa endpoints ausentes ou duplicados nesse mapa.

As entradas são arrays documentados com shapes PHPDoc; as respostas são classes
nomeadas com propriedades PHPDoc somente leitura. Isso oferece sugestões no
editor sem duplicar toda a validação do servidor. As coleções propagam o tipo
dos itens no `foreach` e no iterador de todas as páginas. O gerador normaliza
objetos anuláveis e recusa composições que não consegue representar.

O SDK valida a configuração, os limites do transporte e a estrutura publicada
das respostas. Essa conferência exige campos obrigatórios e os tipos de objetos,
listas e escalares, mas preserva campos e enums novos. Formatos, limites,
permissões e regras de negócio continuam sendo validados pela API.

## Atualizar o contrato

1. Escolha uma revisão pública aprovada e baixe o JSON por sua URL imutável.
   Confira o manifesto e o checksum dessa revisão antes de substituir o snapshot.
2. Revise mudanças estruturais e semânticas: campos, status, idempotência,
   paginação, permissões, ambiente e resultados pendentes. Atualize
   `contracts/source.json` com a origem e o hash conferidos.
3. Ajuste o mapa de serviços quando necessário e execute `composer generate`.
   Revise as classes, os parâmetros e a referência gerados.
4. Execute `composer check` no workspace de engenharia. Ele reúne a conferência
   dos gerados, lint, análise estática, cenários de contrato e transporte e o
   pacote. Use o mesmo perfil do preflight ao preparar uma entrega. Confira
   todos os arquivos alterados e novos.
5. Atualize os guias de uso e registre mudanças de compatibilidade antes de
   publicar uma versão. Mudanças incompatíveis exigem uma versão principal.

Não edite o snapshot para inventar um endpoint ou aceitar uma regra diferente.
Corrija a fonte canônica da API e aguarde sua publicação quando houver um erro
de contrato. A geração confere o hash, mas a revisão de compatibilidade exige
analisar o significado das respostas, inclusive `202` e erros.

## Compatibilidade da biblioteca PHP

`contracts/php-api.json` registra o contrato de símbolos da biblioteca: classes,
interfaces, métodos, parâmetros, retornos, propriedades, constantes e tipos
documentados. Ele é independente do OpenAPI e não é regravado pela geração HTTP.

Execute `composer check-compatibility` para conferir a implementação atual contra
essa base. O check permite adições compatíveis e recusa remoções ou alterações
que invalidem o uso registrado. Descrições de documentação não entram nessa
comparação; os tipos documentados de modelos e coleções entram. Classes marcadas
`@internal` e o catálogo interno `Generated\Contract` ficam fora. O valor de
`RaimsClient::VERSION` pode mudar entre releases; sua existência e seu tipo
continuam protegidos.

Alterações de tipos complexos passam por revisão explícita; o check usa uma
comparação conservadora nesses casos. Parâmetros opcionais adicionais também
exigem cuidado em métodos que outras classes possam implementar ou sobrescrever.

Uma atualização deliberada da base usa `composer snapshot-api -- --write`.
Revise o diff do manifesto e o impacto nas integrações antes de aceitar uma
mudança. Regenerar o OpenAPI ou atualizar a base não torna uma alteração
incompatível segura para os consumidores.

## Diagnóstico da integração

A aplicação consumidora define seus próprios alertas de indisponibilidade,
resposta inválida e recuperação. Agrupe falhas e registre somente metadados
necessários, como HTTP, código e `Request-Id`. Não envie alertas por requisição
nem inclua chaves, corpos, documentos pessoais ou URLs temporárias.

O SDK expõe exceções de conexão, HTTP e protocolo para essa finalidade. Ele não
envia telemetria nem notificações externas. Uma recuperação deve se basear em
novas respostas válidas, sem repetir automaticamente uma escrita anterior.

## Preparação da primeira versão estável

Os fontes estão disponíveis por VCS na branch de desenvolvimento. Para a
primeira versão estável, ainda é necessário:

- Revisar os consumidores, preparar o CI nos runners CodeBuild autorizados e
  publicar a release e o pacote no Packagist. Os fontes no GitHub, por si só,
  não representam uma versão estável distribuída pelo Composer.
- Incluir o SDK nas fontes aprovadas do atendimento em `raims-developer-docs` e
  `Raims-Chat/docs/knowledge-sources.md`. A orientação deve explicar finalidade,
  quando sugerir o SDK, instalação, requisitos, uso no servidor, obtenção da chave,
  permissões, ambiente, disponibilidade, paginação, idempotência, respostas
  pendentes e validação de webhooks. Conferir os caminhos atuais, atualizar a
  fonte responsável, regenerar os catálogos e validar as respostas da IA.
  O SDK atual não exige uma nova ferramenta de consulta nem alteração no
  contrato de contexto do chat.
- Publicar a nota no changelog de `raims-developer-docs` na data efetiva da
  primeira distribuição, com instalação e compatibilidade confirmadas.

CI, release, Packagist e publicação dos guias seguem suas autorizações próprias.
