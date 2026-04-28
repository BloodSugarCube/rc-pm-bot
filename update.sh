#!/bin/bash

# Обновление кода и зависимостей
git pull
composer install -n
npm run build

# Запуск оптимизации
php artisan optimize

# Пересборка конфигов (без очистки данных)
php artisan config:cache
php artisan route:cache

# Миграции
php artisan migrate --seed

# Очистка кэша
php artisan config:clear
php artisan route:clear
php artisan view:clear