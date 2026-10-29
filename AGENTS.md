# Raims PHP SDK

Leia o README e os guias de contribuição e manutenção antes de alterar o SDK.
O SDK é um consumidor do contrato público. Não importa código dos hosts Raims.

- Descreva o SDK pelos recursos e contratos da Raims. Não use marcas de outras
  empresas como inspiração, comparação ou referência de design, arquitetura
  ou implementação em README, guias, comentários, exemplos, nomes próprios
  do SDK ou textos de commits, pull requests e releases. Antes de concluir e
  antes de publicar, confira os arquivos alterados e novos e os links da entrega.
  Preserve avisos de licença, atribuições de código incorporado e identificadores
  necessários às dependências reais; não atribua autoria exclusiva a código
  de terceiros.
- Namespace `Raims`, autoload PSR-4 e PHP 8.2 ou superior em 64 bits.
- `src/Service`, `src/Model`, `src/Generated/Contract.php` e `docs/reference.md`
  são gerados por `php scripts/generate.php`. `AbstractService.php` e
  `SimulationsService.php` são mantidos manualmente.
- `src/EnumValues.php` também é gerado. Os nomes derivam de valores string ou
  rótulos numéricos explícitos do contrato público; não manter um mapa manual.
- Respostas passam pelo validador estrutural gerado antes da hidratação. Preserve
  campos futuros, tipos das coleções e a distinção entre ausência e null.
- O transporte reutiliza conexões e deve limpar método, headers, corpo e callbacks
  entre chamadas. Diagnósticos contêm somente metadados, sem IDs de recurso,
  query, credencial ou payload, e não alteram resultados ou retries de escrita.
- A revisão e o hash da fonte pública ficam em `contracts/source.json`.
  Não use o contrato privado do Dashboard nem endpoints sem publicação confirmada.
- `contracts/php-api.json` é a base independente dos símbolos públicos do SDK.
  `composer check-compatibility` somente confere; a atualização exige o comando
  explícito `composer snapshot-api -- --write` e revisão do diff. A geração do
  OpenAPI não pode regravar essa base nem silenciar uma incompatibilidade.
- Exceções intencionais do SDK implementam `ExceptionInterface` e preservam a
  compatibilidade com SPL. Não encapsule erros nativos da engine ou da aplicação.
- Preserve nomes JSON, centavos inteiros, Unix segundos, null e omissão.
  Mapas vazios em entradas usam `(object) []`. Não gerar idempotência nem repetir
  escritas automaticamente. `202` mantém seu significado de resultado pendente.
- Novos serviços devem ter uma entrada explícita no gerador. A geração precisa
  cobrir cada endpoint fixado exatamente uma vez e falhar diante de divergência.
- Transporte autenticado não baixa URLs externas, não envia arquivos, não segue
  redirects e não oferece headers arbitrários.
- Testes, simuladores, dependências de validação e relatórios ficam somente em
  `C:\server\raims\.tmp\raims-php-sdk`, fora deste repositório. Nunca versioná-los.
- Confira `composer validate --strict`, `composer check-generated`, lint PHP,
  cenários de contrato e o pacote de distribuição, conforme `CONTRIBUTING.md`.
- Preserve o código local sem commit e sem staging até pedido explícito.
  Repositório público, commit/push, release/Packagist e deploy são etapas distintas.
- CI futuro usa os runners CodeBuild autorizados pela Raims. Este repositório
  não instala workflows de publicação nem cria infraestrutura.
