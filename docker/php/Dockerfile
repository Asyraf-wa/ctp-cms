# Shared image for app (php-fpm), queue, scheduler and vite.
# Matches production shared hosting: PHP 8.4. Node is dev-only (production gets pre-built assets).
FROM php:8.4-fpm-bookworm

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl zip bcmath gd exif opcache pcntl \
    && apt-get update && apt-get install -y --no-install-recommends git unzip curl ca-certificates default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Run as the host user so files created in bind mounts stay editable from WSL/Windows.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g ${GID} www-data && usermod -o -u ${UID} -g ${GID} www-data \
    && mkdir -p /home/www-data && chown www-data:www-data /home/www-data \
    && usermod -d /home/www-data www-data

COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
