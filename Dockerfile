FROM php:8.0-fpm

# php:8.0-fpm собран на Debian 11 (bullseye), а её поддержка закончилась:
# на deb.debian.org индекс bullseye-security ещё ссылается на пакеты, но
# сами .deb удалены — сборка падала на 404 (rsync, libpng-dev, libxml2).
# Берём bullseye из archive.debian.org. security-репозитория там пока нет,
# поэтому без него: обновлений безопасности для bullseye всё равно больше
# не выходит. Valid-Until у архивных Release истёк — проверку отключаем.
# Настоящее решение — перейти на поддерживаемую версию PHP.
RUN printf '%s\n' \
        'deb http://archive.debian.org/debian bullseye main' \
        'deb http://archive.debian.org/debian bullseye-updates main' \
        > /etc/apt/sources.list \
    && rm -f /etc/apt/sources.list.d/* \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99archive

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    rsync \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Лимиты загрузки файлов запекаем в образ
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Код приложения собираем в /app; entrypoint при старте копирует его
# в общий volume /var/www (его монтирует и nginx).
WORKDIR /app
COPY . /app
# Composer качает дистрибутивы параллельно, и на нестабильном канале zip'ы
# приезжают обрезанными («is not a zip archive»). Поэтому: ограничиваем
# параллелизм, кэшируем загрузки между сборками (cache mount), повторяем
# попытку, а на третьей уходим в --prefer-source (git вместо zip).
ENV COMPOSER_HOME=/tmp/composer \
    COMPOSER_MAX_PARALLEL_HTTP=4 \
    COMPOSER_PROCESS_TIMEOUT=900

RUN --mount=type=cache,target=/tmp/composer \
    set -eu; \
    args="--no-interaction --no-plugins --no-scripts --optimize-autoloader"; \
    for attempt in 1 2 3; do \
        if [ "$attempt" = 3 ]; then mode=--prefer-source; else mode=--prefer-dist; fi; \
        echo ">>> composer install: попытка $attempt ($mode)"; \
        if composer install $args $mode; then ok=yes; break; fi; \
        composer clear-cache || true; \
        sleep 5; \
    done; \
    [ "${ok:-no}" = yes ]

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
