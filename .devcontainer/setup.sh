#!/usr/bin/env bash
# Installs the PHP extensions this project needs (see backend/composer.lock):
# intl (CodeIgniter 4), zip (PhpSpreadsheet), gd, mysqli, pdo_mysql.
set -euo pipefail

# The base image ships a Yarn apt repo whose signing key has expired/gone
# missing, which makes `apt-get update` exit non-zero. We don't need Yarn's
# apt repo here, so drop it, and don't let a stray repo error abort the setup.
grep -rl "dl.yarnpkg.com" /etc/apt/sources.list /etc/apt/sources.list.d/ 2>/dev/null \
  | xargs -r sudo rm -f
sudo apt-get update || echo "WARN: apt-get update reported errors; continuing"

sudo apt-get install -y --no-install-recommends \
  libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev

sudo docker-php-ext-configure gd --with-freetype --with-jpeg
# sudo drops PHP_INI_DIR, which docker-php-ext-install needs to write the
# conf.d/docker-php-ext-*.ini files that enable each extension. Pass it through.
sudo env PHP_INI_DIR="${PHP_INI_DIR:-/usr/local/etc/php}" \
  docker-php-ext-install -j"$(nproc)" intl zip gd mysqli pdo_mysql

echo "--- PHP extensions check ---"
php -m | grep -Ei '^(intl|zip|gd|mysqli|pdo_mysql)$'
