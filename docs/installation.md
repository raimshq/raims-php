# Instalação

O SDK requer PHP 8.2 ou superior em 64 bits, Composer 2 e as extensões `curl`,
`json` e `hash`. Verifique o ambiente com `php -v`, `php -m` e
`composer check-platform-reqs` após instalar as dependências.

## Instalar os fontes do GitHub

No projeto que consumirá o SDK, adicione o repositório público como fonte VCS
do Composer e instale a branch de desenvolvimento:

Se o projeto ainda não tiver `composer.json`, crie-o com `composer init` antes
dos comandos abaixo.

```text
composer config repositories.raims-php vcs https://github.com/raimshq/raims-php
composer require raims/raims-php:dev-main
```

`dev-main` acompanha a branch `main`. Mantenha o `composer.lock` da sua aplicação
para repetir a instalação da mesma revisão. Atualizações do SDK ficam sob o
controle do projeto consumidor. Ainda não há release estável nem pacote no
Packagist.

## Usar um checkout local

Para desenvolver com uma cópia local, use um repositório Composer do tipo `path`.
Ajuste `../Raims-PHP` para a localização do checkout:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../Raims-PHP",
            "options": {
                "symlink": true,
                "versions": {"raims/raims-php": "0.1.x-dev"}
            }
        }
    ],
    "require": {
        "raims/raims-php": "0.1.x-dev"
    }
}
```

Execute `composer update raims/raims-php` no projeto consumidor.

## Carregar o cliente

Depois da instalação por qualquer um dos caminhos, use o autoload do Composer:

```php
require __DIR__ . '/vendor/autoload.php';
```

O repositório usa o nome de pacote `raims/raims-php` e o namespace `Raims`.
O SDK não exige bibliotecas de terceiros em produção.

## Credenciais

Obtenha uma chave da sua conta conforme o
[guia de autenticação](https://docs.raims.com/br/integracoes/autenticacao).
Guarde-a em uma variável de ambiente ou no cofre de segredos da aplicação.
As permissões e restrições da chave continuam sendo validadas pela API.

Uma chave Sandbox usa a mesma URL-base da chave de Produção. Não há opção de
ambiente no SDK. As simulações exigem uma chave Sandbox; entregas de e-mail e
webhooks ainda podem alcançar destinos externos nesse ambiente.

## Distribuição

Este checkout não instala workflows de publicação, não cria releases e não
publica no Packagist. A versão `0.1.0-dev` identifica o desenvolvimento do cliente;
ela é independente da versão HTTP `/v1` e da versão do contrato fixado.
