#!/bin/sh
set -e

# Код «запечён» в образ по пути /app. При старте копируем его в общий
# volume /var/www (его же монтирует nginx), сохраняя смонтированные с хоста
# storage и .env.
rsync -a --delete \
  --exclude '/storage' \
  --exclude '/.env' \
  /app/ /var/www/

# storage монтируется с хоста и может быть пустым — создаём стандартную
# структуру Laravel, иначе artisan падает с "Please provide a valid cache path".
mkdir -p \
  /var/www/storage/framework/cache/data \
  /var/www/storage/framework/sessions \
  /var/www/storage/framework/views \
  /var/www/storage/logs \
  /var/www/storage/app/public

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

exec "$@"
