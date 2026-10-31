# ---------- 1) PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- 2) Front-end assets (Vite + Tailwind + Flux CSS from vendor) ----------
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------- 3) Runtime: FrankenPHP (PHP 8.4 + Caddy) ----------
FROM dunglas/frankenphp:1-php8.4-bookworm
RUN install-php-extensions pdo_sqlite intl zip bcmath opcache pcntl

WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN rm -f public/hot bootstrap/cache/*.php \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
             storage/logs bootstrap/cache database \
 && php artisan package:discover --ansi \
 && sed -i 's/\r$//' docker/start.sh \
 && chmod +x docker/start.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    PORT=8080

EXPOSE 8080
CMD ["/app/docker/start.sh"]
