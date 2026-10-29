# Paginação

Listagens paginadas retornam `list` e `page`. O método `all()` busca uma página;
iterar essa página não inicia outras requisições.

```php
$page = $raims->customers->all(['page_size' => 50]);

foreach ($page as $customer) {
    // Somente os clientes desta página.
}

$count = count($page);
$firstItem = $page->first();
$lastItem = $page->last();
$empty = $page->isEmpty();
$next = $page->nextPage();
$previous = $page->previousPage();
```

`first()` e `last()` retornam um item tipado ou `null` quando a página está vazia.
`isEmpty()` informa somente se a página atual não tem itens. Esses métodos não
buscam outras páginas. Uma página vazia pode ter continuação; consulte
`page.has_next` ou use o iterador automático para percorrer os demais resultados.

`nextPage()` retorna `null` quando `page.has_next` é falso. `previousPage()` faz
o mesmo com `page.has_previous`. Os métodos preservam filtros e opções da
requisição e removem o cursor da direção contrária.

## Percorrer todas as páginas

```php
$page = $raims->charges->all([
    'customer_id' => $customerId,
    'page_size' => 100,
]);

foreach ($page->autoPagingIterator() as $charge) {
    // Processar cada cobrança conforme a autorização da aplicação.
}
```

O iterador busca a página seguinte quando necessário. Sair do `foreach` interrompe
as novas buscas. Cada página é uma leitura HTTP e pode falhar, gerar um novo
`Request-Id` ou atingir um limite. O SDK não fornece um snapshot do conjunto;
alterações concorrentes seguem as garantias da listagem da API.

Uma página vazia pode ter continuação, especialmente em destinos de saque.
O SDK segue `has_next` e `next_after` mesmo nesse caso. Cursores repetidos,
ausentes ou inválidos encerram a iteração com exceção, evitando um loop.
`autoPagingIterator()` percorre a direção de avanço; para voltar, use
`previousPage()` explicitamente.

## Controle manual

```php
$first = $raims->customers->all(['page_size' => 20]);
if ($first->page->has_next) {
    $second = $raims->customers->all([
        'page_size' => 20,
        'after' => $first->page->next_after,
    ]);
}
```

Copie os cursores retornados pela API. Não calcule posições por ID nem envie
`after` e `before` juntos. Mantenha os mesmos filtros e a mesma ordenação.
A lista de tipos de evento de webhook contém apenas `list`, sem paginação:
use `$raims->webhookEventTypes->all()->list`.
