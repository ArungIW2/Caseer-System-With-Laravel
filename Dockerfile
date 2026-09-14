FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json ./
RUN npm install
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.3-cli AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql bcmath intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-dev --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
