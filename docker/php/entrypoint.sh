#!/bin/sh
set -e

# Код «запечён» в образ по пути /app. При старте копируем его в общий
# volume /var/www (его же монтирует nginx), сохраняя смонтированные с хоста
# storage и .env.
rsync -a --delete \
  --exclude '/storage' \
  --exclude '/.env' \
  /app/ /var/www/

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

exec "$@"
