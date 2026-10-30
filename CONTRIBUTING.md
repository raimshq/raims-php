# Contribuir

Leia o [guia de manutenção](docs/maintenance.md) e as instruções em `AGENTS.md`.
Relate problemas reproduzíveis sem credenciais, dados de clientes ou URLs que
permitam acessar arquivos e checkouts.

## Textos e exemplos

Descreva os recursos, contratos e condições de uso do SDK diretamente.
Não apresente outras empresas como inspiração, comparação ou referência de
design, arquitetura ou implementação. A orientação vale também para comentários,
exemplos, nomes de componentes e textos de commits, pull requests e releases.
Revise os arquivos alterados e novos e seus links antes de enviar a contribuição.

Preserve avisos de licença, atribuições de código incorporado e nomes necessários
às dependências e integrações reais. Código de terceiros deve manter sua autoria
e procedência.

## Validar alterações

```text
composer validate --strict
composer dump-autoload --no-dev
composer check-generated
composer check-compatibility
composer check
```

`composer check` usa o validador canônico do Developer Toolkit no checkout
vizinho. Ele confere o contrato, a geração, a sintaxe, os tipos vistos pelo
integrador, as regressões e o pacote. Os arquivos físicos das suítes são
materializados somente em `.tmp`; o comando não chama a API real.

Na primeira execução da estação, use `composer check -- --prepare-tools` para
obter a versão fixada do PHPStan, com checksum conferido. As ferramentas e os
resultados ficam em `.tmp`. Sem preparo explícito, ferramenta ausente ou hash
divergente bloqueia a validação. `RAIMS_PYTHON` permite selecionar o Python
compatível com o ambiente de engenharia.

Um clone público isolado não contém o Toolkit de engenharia. Nessa situação,
o comando informa a dependência ausente; não declara um check parcial aprovado.
Os comandos de geração e lint continuam disponíveis no código do SDK.

Execute `php -l` em cada arquivo PHP, incluindo o gerador. Verifique também os
tipos e os exemplos com a versão de PHP que será usada pela aplicação.
Dependências e arquivos de validação ficam fora do pacote. No workspace Raims,
use exclusivamente `C:\server\raims\.tmp\raims-php-sdk` para suítes, simuladores,
ferramentas e relatórios. Não os inclua em commits nem no arquivo de distribuição.

Para alterações no transporte ou no gerador, a validação precisa cobrir:

- Cada endpoint fixado, com método, caminho, query, JSON e status de sucesso.
- JSON em DELETE, POST sem corpo, `204`, respostas `202` e erros sem envelope.
- Null, omissão, false, zero, mapas vazios e campos futuros.
- Representações equivalentes de inteiros, valores grandes e frações que não
  podem ser aceitas por arredondamento.
- Opções por requisição, isolamento entre clientes e argumentos sensíveis.
- Retries somente de leitura, idempotência estável e `Retry-After`.
- Paginação nos dois sentidos, filtros, páginas vazias e ciclos.
- TLS verificado, redirects bloqueados e timeouts usando transporte local.
- Assinatura de webhook, rotação, relógio, Base62 e bytes originais do corpo.

Use transporte falso e receptores locais. O Sandbox pode emitir comunicações
externas; não faça testes contra a API real como parte da validação da biblioteca.
Encerre os processos temporários e confira suas portas antes de concluir.

## Revisar a distribuição

O check confere o arquivo de `composer archive` e um arquivo Git equivalente ao
ZIP de distribuição do GitHub. As regras `archive.exclude` e `export-ignore`
devem produzir o mesmo conjunto de arquivos, sem alterar o índice ou os objetos
Git do checkout do SDK. Toda preparação acontece em `.tmp`, sem criar um commit.

O pacote deve conter os fontes PHP, os metadados do
Composer, a licença e os guias de uso. Contratos, gerador, ferramentas,
dependências de desenvolvimento e evidências não precisam ir ao consumidor.

O workflow `Validate PHP SDK` executa os checks canônicos em PHP 8.2, 8.3, 8.4
e 8.5, com versões fixadas, e aplica a análise de segurança em um job separado.
Ele roda em pushes do próprio repositório e por execução manual, nos runners
CodeBuild autorizados. O Toolkit privado fica fixado por commit.

Antes de promover uma alteração para `main`, valide o commit em uma branch
candidata. A etapa `Deploy` do preflight exige a execução aprovada para o mesmo
SHA, com todos os jobs esperados concluídos. Promova esse commit sem alterar
seu conteúdo. Uma execução cancelada, incompleta ou de outro commit não serve
como aprovação.

A validação não publica uma release nem um pacote no Packagist. Não acrescente
um runner pessoal ou hospedado pelo GitHub para contornar os checks.

## Compatibilidade

Preserve o namespace, as assinaturas públicas e os nomes do contrato HTTP.
O manifesto `contracts/php-api.json` é conferido por `composer check-compatibility`
e pelo check completo. Para atualizar essa base deliberadamente, use
`composer snapshot-api -- --write` e revise o diff conforme o
[guia de manutenção](docs/maintenance.md#compatibilidade-da-biblioteca-php).
Não acrescente retries de escrita, geração silenciosa de idempotência ou
validação de domínio incompatível. Documente alterações de comportamento e
compatibilidade em uma nota de versão; refactors internos não precisam virar
orientação ao integrador.
