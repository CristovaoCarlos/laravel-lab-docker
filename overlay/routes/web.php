<?php

use Illuminate\Support\Facades\Route;

// Página inicial do laboratório: lista os endpoints disponíveis.
Route::get('/', function () {
    $html = <<<'HTML'
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laravel Lab</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:46rem;margin:2rem auto;padding:0 1rem;line-height:1.5;color:#1f2933}
    code{background:#eef1f5;padding:.1rem .35rem;border-radius:4px}
    table{border-collapse:collapse;width:100%}
    td,th{border-bottom:1px solid #d9dee5;padding:.4rem .5rem;text-align:left}
  </style>
</head>
<body>
  <h1>Laravel Lab</h1>
  <p>Laboratório do guia de estudo: Eloquent, migrations, filas, middlewares, providers, Form Requests, API Resources e testes.</p>
  <h2>Usuários (senha: <code>password</code>)</h2>
  <p><code>admin@lab.test</code> · <code>editor@lab.test</code> · <code>leitor@lab.test</code></p>
  <h2>Endpoints</h2>
  <table>
    <tr><th>Método</th><th>Rota</th><th>Acesso</th></tr>
    <tr><td>POST</td><td><code>/api/login</code></td><td>público (devolve token)</td></tr>
    <tr><td>GET</td><td><a href="/api/posts"><code>/api/posts</code></a></td><td>público</td></tr>
    <tr><td>GET</td><td><code>/api/posts/{id}</code></td><td>público (só publicados)</td></tr>
    <tr><td>GET</td><td><code>/api/me</code></td><td>autenticado</td></tr>
    <tr><td>POST</td><td><code>/api/posts</code></td><td>admin ou editor</td></tr>
    <tr><td>PUT</td><td><code>/api/posts/{id}</code></td><td>admin ou dono do post</td></tr>
    <tr><td>POST</td><td><code>/api/posts/{id}/publish</code></td><td>admin ou dono (dispara job na fila)</td></tr>
    <tr><td>DELETE</td><td><code>/api/posts/{id}</code></td><td>somente admin</td></tr>
    <tr><td>POST</td><td><code>/api/utils/cpf</code></td><td>público (regra customizada)</td></tr>
  </table>
</body>
</html>
HTML;

    return response($html);
});
