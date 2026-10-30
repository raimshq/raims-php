# Validação antes da distribuição

- [x] Consolidar o validador do SDK e a análise de segurança na `main` do
  Developer Toolkit, fixar essa revisão no CI e validar PHP 8.2, 8.3, 8.4 e 8.5
  nos runners CodeBuild autorizados antes da promoção do mesmo commit.
  A mudança é interna ao processo de engenharia e não altera endpoints,
  instalação, permissões ou comportamento para o integrador; não exige nova
  orientação no atendimento nem nota no changelog público.

- [ ] Concluir a primeira distribuição `raims/raims-php` 0.1.0 e a orientação
  de instalação pelo Composer.
  - Preparar e conferir a release do mesmo commit aprovado nos cinco jobs do
    CI AWS e a atualização pelo workflow `packagist-release.yml`. Confirmar
    a versão no Packagist e uma instalação limpa com
    `composer require raims/raims-php:^0.1.0` antes de anunciar disponibilidade.
  - Publicar os guias BR/EN `integracoes/sdk-php`, a navegação e a nota da
    primeira distribuição em `raims-developer-docs`, com a data real de
    disponibilização. Os textos locais estão preparados para 30/10/2026 e
    aguardam a confirmação do pacote e o ciclo autorizado de publicação.
  - Fontes do atendimento: guia e changelog públicos do Docs, catálogo
    regenerado e mapa `Raims-Chat/docs/knowledge-sources.md`. Validar em BR/EN
    finalidade, instalação, PHP 64 bits, uso no backend, credenciais,
    permissões, ambiente, paginação, idempotência, HTTP 202 e webhooks.
    Reutilizar a fonte Docs e a bateria canônica; não há novo schema ou
    ferramenta financeira. Encerrar somente após a conferência no destino.
