FROM php:8.0-fpm

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
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist \
    --optimize-autoloader

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
