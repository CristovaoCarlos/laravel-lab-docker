# Laravel Lab: laboratório Docker do Guia de Estudo de Laravel

Ambiente pronto para praticar os oito temas do guia (Eloquent, migrations, filas, middlewares, Service Providers, Form Requests, API Resources e testes) sem instalar PHP, Composer ou MySQL na sua máquina. Tudo roda em containers com o **Docker** (plataforma que empacota aplicações e suas dependências em ambientes isolados) e o **Docker Compose** (ferramenta que sobe vários containers de uma vez a partir de um arquivo `docker-compose.yml`).

## O que sobe

| Serviço | O que é | Endereço |
| --- | --- | --- |
| `app` | Laravel (framework PHP para aplicações web) servido pelo `artisan serve` (servidor de desenvolvimento embutido) | http://localhost:8000 |
| `queue` | Worker (processo em segundo plano) que consome a fila de jobs, com `php artisan queue:work` | sem porta |
| `mysql` | MySQL 8.4 (banco de dados relacional) com volume persistente | `localhost:3307` |
| `adminer` (opcional) | Adminer (interface web leve para inspecionar bancos) | http://localhost:8080 |

## Como usar

Pré-requisito: Docker com Docker Compose v2 (já vêm no Docker Desktop). No Linux ou WSL (Windows Subsystem for Linux, Linux dentro do Windows), rode `id -u` e `id -g` e ajuste `HOST_UID` e `HOST_GID` no arquivo `.env` se forem diferentes de 1000.

```bash
docker compose up -d --build     # ou: make up
docker compose logs -f app       # acompanhe a primeira execução
```

**A primeira execução demora alguns minutos**, porque o container baixa o Laravel com o **Composer** (gerenciador de dependências do PHP), instala o Sanctum (pacote oficial de autenticação por token para APIs), aplica os exemplos do guia, roda as migrations e popula o banco com o seeder. Quando aparecer `laboratório preparado`, abra http://localhost:8000. Nas próximas vezes, sobe em segundos.

O projeto Laravel gerado fica na pasta `src/`, no seu computador: edite pelo seu editor favorito e o container enxerga na hora. Ele não é sobrescrito nas execuções seguintes.

### Comandos do dia a dia

```bash
make test        # roda a suíte PHPUnit (framework de testes automatizados do PHP)
make fresh       # recria o banco (migrate:fresh) e roda os seeders
make shell       # terminal dentro do container do app
make tinker      # console interativo do Laravel (tinker)
make queue-logs  # veja o worker processando jobs
make tools       # sobe também o Adminer
make down        # para tudo (o banco continua guardado no volume)
```

Sem `make`, use direto: `docker compose exec app php artisan test`, `docker compose exec app php artisan migrate:fresh --seed` etc. O `artisan` é a CLI (interface de linha de comando) do Laravel.

## Testando a API

Usuários criados pelo seeder (senha de todos: `password`): `admin@lab.test`, `editor@lab.test` e `leitor@lab.test`.

```bash
# 1) login: devolve um token
curl -s -X POST localhost:8000/api/login -H 'Accept: application/json' \
  -d 'email=editor@lab.test' -d 'password=password'

# 2) lista pública (paginada), sem token
curl -s localhost:8000/api/posts -H 'Accept: application/json'

# 3) cria um post (troque SEU_TOKEN)
curl -s -X POST localhost:8000/api/posts -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN' \
  -d 'title=Meu primeiro post' -d 'body=Um texto com mais de vinte caracteres.'

# 4) publica o post: dispara o job; veja o worker com `make queue-logs`
curl -s -X POST localhost:8000/api/posts/ID/publish -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN'
```

Sempre envie `Accept: application/json`; sem ele, erros de validação viram redirecionamentos em vez de respostas 422.

## Mapa: tema do guia → arquivos em `src/`

| Tema do guia | Onde ver e mexer |
| --- | --- |
| Eloquent | `app/Models/` (Post, Tag, Comment, User): relacionamentos, scope `published`, accessor/mutator de `title`, soft deletes |
| Migrations, factories, seeders | `database/migrations/2026_01_01_*`, `database/factories/`, `database/seeders/DatabaseSeeder.php` |
| Filas e jobs | `app/Jobs/NotifyFollowersOfNewPost.php` (tries, backoff, `failed()`), disparado em `PostController@publish` com `afterCommit()` |
| Middlewares | `app/Http/Middleware/` (`active`, `role:admin,editor`, `LogRequestTime`), registrados em `bootstrap/app.php` |
| Service Providers | `app/Providers/LabServiceProvider.php` (`bind`, `singleton`, `boot`), `app/Contracts/SlugGenerator.php`, `app/Services/`, `config/lab.php` |
| Form Requests | `app/Http/Requests/` e a regra customizada `app/Rules/Cpf.php` |
| API Resources | `app/Http/Resources/` (`whenLoaded`, `when`, `whenCounted`, paginação com `links` e `meta`) |
| Testes | `tests/Feature/` e `tests/Unit/` |

Ideias de exercício: troque `LAB_SLUG_DRIVER` para `timestamp` no `.env` do Laravel e veja o slug mudar; comente o `with(['author','tags'])` no `PostController@index` e observe o N+1; force uma exceção no job e acompanhe as tentativas com backoff e a tabela `failed_jobs`; escreva um novo Form Request e os testes dele.

## Os testes

`make test` roda os testes com **SQLite em memória** (banco leve que vive na RAM), configurado com `force="true"` no `phpunit.xml`. Assim eles nunca tocam no MySQL de desenvolvimento, mesmo usando `RefreshDatabase`. Os testes de Feature cobrem endpoints, validação, autorização, filas (com `Bus::fake()`), middlewares, Eloquent e o container; os de Unit cobrem classes em PHP puro.

## Configurações úteis

- **Versão do Laravel:** defina `LARAVEL_VERSION` no `.env` da raiz (por exemplo `^12.0`) **antes** da primeira execução. Vazio instala a última versão estável.
- **Portas em uso:** mude `APP_PORT`, `DB_PORT_HOST` ou `ADMINER_PORT` no `.env` da raiz.
- **Recomeçar do zero:** `docker compose down -v` apaga o banco; apague também o conteúdo de `src/` (exceto `.gitkeep`) para reinstalar o Laravel.
- **Só o banco:** `make fresh` recria as tabelas sem reinstalar nada.

## Problemas comuns

- **Permissão negada ao criar arquivos em `src/`:** o UID/GID do container não bate com o seu. Ajuste `HOST_UID` e `HOST_GID` no `.env` e rode `docker compose up -d --build`.
- **`src/` não está vazio e não contém um projeto Laravel:** a pasta precisa estar vazia (só com `.gitkeep`) na primeira execução.
- **Lento no Windows:** deixe a pasta do laboratório dentro do sistema de arquivos do WSL (por exemplo `~/laravel-lab`), não em `C:\`.
- **Worker parado ou com código antigo:** `docker compose restart queue` (o worker guarda o código em memória, então reinicie após mudar um job).

> As senhas do `.env` são apenas para estudo local. Não use este ambiente em produção: o `artisan serve` é um servidor de desenvolvimento e o `APP_DEBUG` fica ligado.
