#!/usr/bin/env bash
# Entrypoint do laboratório Laravel.
#  - papel "app":   prepara o projeto na primeira execução e depois executa o comando (artisan serve)
#  - papel "queue": espera o projeto ficar pronto e executa o comando (queue:work)
set -euo pipefail

APP_DIR=/var/www/html
ROLE="${LAB_ROLE:-app}"
cd "$APP_DIR"

log() { echo "[lab:${ROLE}] $*"; }

if [ "$ROLE" = "queue" ]; then
  until [ -f "$APP_DIR/.lab-bootstrapped" ] && [ -f "$APP_DIR/vendor/autoload.php" ]; do
    log "aguardando o serviço app preparar o projeto..."
    sleep 3
  done
  log "iniciando: $*"
  exec "$@"
fi

# Define KEY=VALUE no .env do Laravel (substitui a linha, comentada ou não, ou acrescenta).
set_env() {
  local key="$1" value="$2" escaped
  escaped="$(printf '%s' "$value" | sed -e 's/[\/&|]/\\&/g')"
  if grep -qE "^#?[[:space:]]*${key}=" .env; then
    sed -i -E "s|^#?[[:space:]]*${key}=.*|${key}=${escaped}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

configure_env() {
  [ -f .env ] || cp .env.example .env
  set_env APP_NAME '"Laravel Lab"'
  set_env APP_ENV local
  set_env APP_DEBUG true
  set_env APP_URL "${LAB_APP_URL:-http://localhost:8000}"
  set_env DB_CONNECTION mysql
  set_env DB_HOST "${LAB_DB_HOST:-mysql}"
  set_env DB_PORT "${LAB_DB_PORT:-3306}"
  set_env DB_DATABASE "${LAB_DB_DATABASE:-laravel_lab}"
  set_env DB_USERNAME "${LAB_DB_USERNAME:-lab}"
  set_env DB_PASSWORD "${LAB_DB_PASSWORD:-lab_secret}"
  set_env SESSION_DRIVER database
  set_env CACHE_STORE database
  set_env QUEUE_CONNECTION database
  set_env MAIL_MAILER log
  set_env LOG_LEVEL debug
  grep -qE '^APP_KEY=base64:' .env || php artisan key:generate --force
}

migrate_with_retry() {
  local i
  for i in $(seq 1 30); do
    if php artisan migrate --force; then
      return 0
    fi
    log "banco indisponível ou migration falhou, tentativa $i/30..."
    sleep 3
  done
  log "ERRO: não foi possível rodar as migrations."
  return 1
}

# 1) Projeto Laravel ainda não existe em ./src: cria.
if [ ! -f artisan ]; then
  # Metadados do Windows (arquivo:Zone.Identifier) criados ao extrair um .zip.
  find . -mindepth 1 -maxdepth 1 -name '*:Zone.Identifier' -delete
  others="$(find . -mindepth 1 -maxdepth 1 ! -name '.gitkeep' | head -n 1)"
  if [ -n "$others" ]; then
    log "ERRO: ./src não está vazio e não contém um projeto Laravel (arquivo artisan)."
    exit 1
  fi
  rm -f .gitkeep
  args=()
  if [ -n "${LARAVEL_VERSION:-}" ]; then
    args+=("${LARAVEL_VERSION}")
  fi
  log "criando o projeto Laravel (composer create-project), pode levar alguns minutos..."
  composer create-project laravel/laravel . "${args[@]}" --no-interaction --prefer-dist
fi

# 2) Dependências (caso o vendor/ tenha sido apagado).
if [ ! -f vendor/autoload.php ]; then
  log "instalando dependências (composer install)..."
  composer install --no-interaction --prefer-dist
fi

# 3) Primeira execução: configura o .env, instala a API (Sanctum), aplica os exemplos do guia.
if [ ! -f .lab-bootstrapped ]; then
  log "primeira execução: configurando o laboratório..."
  configure_env

  log "instalando suporte a API (php artisan install:api, inclui o Sanctum)..."
  php artisan install:api --no-interaction

  log "aplicando os exemplos do guia sobre o projeto..."
  cp -R /opt/overlay/. "$APP_DIR"/
  composer dump-autoload --no-interaction

  migrate_with_retry
  php artisan db:seed --force
  touch .lab-bootstrapped
  log "laboratório preparado."
else
  [ -f .env ] || configure_env
  migrate_with_retry
fi

log "iniciando: $*"
exec "$@"
