FROM php:8.4-cli-bookworm

# UID/GID do seu usuário no host, para que os arquivos criados em ./src
# pertençam a você e não ao root (ajuste no arquivo .env se necessário).
ARG HOST_UID=1000
ARG HOST_GID=1000

ENV COMPOSER_MEMORY_LIMIT=-1 \
    PHP_CLI_SERVER_WORKERS=4

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip curl libzip-dev libicu-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql zip intl bcmath pcntl \
 && rm -rf /var/lib/apt/lists/* \
 && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/lab.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN groupadd --non-unique --gid "${HOST_GID}" app \
 && useradd --non-unique --uid "${HOST_UID}" --gid "${HOST_GID}" --create-home --shell /bin/bash app \
 && mkdir -p /var/www/html /home/app/.cache/composer /home/app/.config/composer \
 && chown -R "${HOST_UID}:${HOST_GID}" /var/www/html /home/app

COPY docker/entrypoint.sh /usr/local/bin/lab-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/lab-entrypoint && chmod +x /usr/local/bin/lab-entrypoint

USER app
WORKDIR /var/www/html
EXPOSE 8000

HEALTHCHECK NONE
ENTRYPOINT ["lab-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
