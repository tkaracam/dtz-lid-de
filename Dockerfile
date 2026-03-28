# DTZ Learning Platform - Production Dockerfile
# Simple and reliable for Render.com

FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    mbstring \
    zip \
    opcache

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set PHP settings for production
RUN { \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/apache2/php_errors.log'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 128'; \
    echo 'opcache.max_accelerated_files = 4000'; \
    echo 'date.timezone = Europe/Berlin'; \
} > /usr/local/etc/php/conf.d/production.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || \
    (echo "⚠️ Composer install failed, continuing without vendor" && mkdir -p vendor)

# Copy application code
COPY . /var/www/html

# Run composer again after full copy
RUN composer dump-autoload --optimize 2>/dev/null || echo "⚠️ Composer autoload failed"

# Create required directories
RUN mkdir -p /var/www/html/database /var/www/html/storage /var/www/html/logs /var/log/apache2

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/database && \
    chmod -R 775 /var/www/html/storage && \
    chown www-data:www-data /var/log/apache2

# Apache configuration
RUN { \
    echo '<VirtualHost *:8080>'; \
    echo '    DocumentRoot /var/www/html'; \
    echo '    <Directory /var/www/html>'; \
    echo '        Options -Indexes +FollowSymLinks'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# Update Apache ports
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/api/health.php || exit 1

# Use entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
