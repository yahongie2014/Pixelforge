# ============================================================
# Stage 1 — Composer: install PHP dependencies (no dev)
# ============================================================
FROM composer:2.8 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

COPY . .
# Clear any copied host cache files and dump autoloader without dev packages
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --no-dev --optimize --no-scripts


# ============================================================
# Stage 2 — Node: compile frontend assets (Vite + Ziggy)
# ============================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

COPY resources/ resources/
COPY routes/ routes/
COPY vite.config.js postcss.config.js* tailwind.config.js* ./

# Copy Ziggy assets from Composer builder stage
COPY --from=composer-builder /app/vendor/tightenco/ziggy /app/vendor/tightenco/ziggy

RUN npm run build


# ============================================================
# Stage 3 — Production runtime image (PHP 8.2 + Nginx + Supervisor)
# ============================================================
FROM php:8.2-fpm-alpine AS production

LABEL maintainer="PromptEdit <ops@promptedit.com>"
LABEL description="PromptEdit AI Studio — Laravel 12 + Inertia + Vue 3"

# ── System dependencies ────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    mysql-client \
    sed \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS

# ── PHP extensions ─────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/pear

# ── PHP & Nginx configuration ──────────────────────────────
COPY docker/php/php.ini      /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# ── Application code ───────────────────────────────────────
WORKDIR /var/www/html

# Copy pre-packaged vendor from composer stage
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=composer-builder /app .

# Copy pre-compiled Vite frontend bundle
COPY --from=node-builder /app/public/build ./public/build

# ── Storage & permissions ──────────────────────────────────
RUN mkdir -p storage/logs \
             storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/testing \
             storage/framework/views \
             bootstrap/cache \
             /var/log/supervisor \
             /run/nginx \
    && rm -f bootstrap/cache/*.php \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Entrypoint ─────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# Normalize Windows CRLF to Linux LF
RUN sed -i -e 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
