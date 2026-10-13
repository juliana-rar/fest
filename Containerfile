# Festes Majors de Catalunya — application image (PHP 8.3 + Composer + Node 22)
FROM docker.io/library/php:8.3-cli-bookworm

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      git unzip curl libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl exif bcmath opcache \
 && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=docker.io/library/node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=docker.io/library/node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8000 5173
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
