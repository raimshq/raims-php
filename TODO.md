# Validação antes da distribuição

- [x] Consolidar o validador do SDK e a análise de segurança na `main` do
  Developer Toolkit, fixar essa revisão no CI e validar PHP 8.2, 8.3, 8.4 e 8.5
  nos runners CodeBuild autorizados antes da promoção do mesmo commit.
  A mudança é interna ao processo de engenharia e não altera endpoints,
  instalação, permissões ou comportamento para o integrador; não exige nova
  orientação no atendimento nem nota no changelog público.

- [x] Concluir a primeira distribuição `raims/raims-php` 0.1.0 e a orientação
  de instalação pelo Composer.
  - A release `v0.1.0` foi publicada após os cinco jobs AWS do mesmo commit.
    O workflow `packagist-release.yml` atualizou o registro, confirmou o commit
    e comparou os arquivos públicos. A instalação com cache vazio por
    `composer require raims/raims-php`, o autoload e os requisitos de plataforma
    passaram, sem configurar um repositório adicional.
  - Os guias BR/EN `integracoes/sdk-php`, a navegação e a nota da primeira
    distribuição foram publicados em `raims-developer-docs` em 30/10/2026.
    Páginas, catálogo público e revisão implantada foram conferidos.
  - O runtime do atendimento recebeu o catálogo atualizado. Os 14 casos BR/EN
    de instalação, PHP 64 bits, backend, credenciais, permissões, ambiente,
    paginação, idempotência, HTTP 202 e webhooks passaram em Validate e Retrieval
    com o feed publicado. A fonte Docs existente dispensa novo schema ou
    ferramenta financeira; não houve chamada ao modelo nem envio de mensagens.
