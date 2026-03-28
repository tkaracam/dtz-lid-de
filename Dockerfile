# DTZ Learning Platform - Production Dockerfile
# Multi-stage build for optimization

# Stage 1: PHP Dependencies
FROM composer:2 as vendor

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Stage 2: Production Image
FROM php:8.2-apache as production

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    mbstring \
    xml \
    zip \
    bcmath \
    opcache

# Install Redis extension (for caching)
RUN pecl install redis && docker-php-ext-enable redis

# Configure Apache
RUN a2enmod rewrite headers expires ssl

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/custom.ini"

# Copy application code
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/database \
    && chmod -R 775 /var/www/html/storage

# Apache configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/ports.conf /etc/apache2/ports.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/api/health.php || exit 1

# Expose port
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]

# Alternative: Stage 3 - Nginx + PHP-FPM (better performance)
FROM php:8.2-fpm-alpine as nginx

# Install dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq \
    sqlite-libs \
    oniguruma \
    libxml2 \
    libzip \
    curl

# Install PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    sqlite-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        xml \
        zip \
        opcache \
    && apk del .build-deps

# Copy config files
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/database \
    && chmod -R 775 /var/www/html/storage

# Create required directories
RUN mkdir -p /run/nginx /var/log/supervisor

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/api/health.php || exit 1

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
