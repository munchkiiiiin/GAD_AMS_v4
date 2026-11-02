#!/usr/bin/env bash
# Installs the PHP extensions this project needs (see backend/composer.lock):
# intl (CodeIgniter 4), zip (PhpSpreadsheet), gd, mysqli, pdo_mysql.
set -euo pipefail

sudo apt-get update
sudo apt-get install -y --no-install-recommends \
  libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev

sudo docker-php-ext-configure gd --with-freetype --with-jpeg
sudo docker-php-ext-install -j"$(nproc)" intl zip gd mysqli pdo_mysql

echo "--- PHP extensions check ---"
php -m | grep -Ei '^(intl|zip|gd|mysqli|pdo_mysql)$'
