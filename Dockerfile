# Stage 1: Build frontend assets
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install --no-audit --legacy-peer-deps
COPY vite.config.js ./
COPY resources/ resources/
RUN npm run build

# Stage 2: PHP-FPM with app
FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libxml2-dev \
    oniguruma-dev \
    curl \
    mysql-client

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        bcmath \
        gd \
        zip \
        opcache \
        exif \
        pcntl

# Redis extension (needs build tools temporarily)
RUN apk add --no-cache --virtual .build-deps autoconf build-base \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app code
COPY . .

# Copy built frontend from stage 1
COPY --from=frontend /app/public/build ./public/build

# Install PHP deps (production only, no dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# PHP production config
COPY docker/php.ini /usr/local/etc/php/conf.d/99-finance.ini

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]