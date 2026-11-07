#!/bin/sh
set -e

# Переходим в рабочую директорию
cd /var/www/html

# Проверяем, существует ли .env и есть ли в нём APP_KEY
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# Если APP_KEY пустой или отсутствует, генерируем его
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force --no-interaction
fi

# Передаём управление основной команде (php-fpm)
exec "$@"