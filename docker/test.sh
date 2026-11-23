#!/bin/sh
# Тесты в образе, собранном из текущего кода тем же Dockerfile, что и прод.
# Хостовый каталог НЕ монтируется (entrypoint рсинкает /app в /var/www с --delete),
# база — sqlite в памяти из phpunit.xml. Аргументы уходят в artisan test:
#   docker/test.sh --filter=AuthSessionTest
set -e
cd "$(dirname "$0")/.."
docker build -q -t megaladon_back:test . >/dev/null
docker run --rm --entrypoint "" -w /app \
  -e APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" \
  megaladon_back:test php artisan test "$@"
