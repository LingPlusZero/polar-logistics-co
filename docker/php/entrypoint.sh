#!/bin/sh
set -e

cd /var/www/html

[ -f .env ] || cp .env.example .env

# vendor 放在 named volume，首次啟動才需要安裝
[ -f vendor/autoload.php ] || composer install --no-interaction

grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

# depends_on 的 healthcheck 已確保 MySQL 可連線，這裡直接 migrate
php artisan migrate --force

# bind mount 的 storage 由 root 建立，需開放給 php-fpm (www-data) 寫入
mkdir -p storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX,o+rwX storage bootstrap/cache

exec "$@"
