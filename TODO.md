# Validação antes da distribuição

- [ ] Consolidar o validador do SDK e a análise de segurança na `main` do
  Developer Toolkit, fixar essa revisão no CI e validar PHP 8.2, 8.3, 8.4 e 8.5
  nos runners CodeBuild autorizados antes da promoção do mesmo commit.
  A mudança é interna ao processo de engenharia e não altera endpoints,
  instalação, permissões ou comportamento para o integrador; não exige nova
  orientação no atendimento nem nota no changelog público.
