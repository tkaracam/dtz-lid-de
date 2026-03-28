#!/bin/bash

# DTZ Learning Platform - Docker Entrypoint
# This script runs when the container starts

set -e

echo "🚀 Starting DTZ Lernplattform..."

# Create database directory if it doesn't exist
mkdir -p /var/www/html/database
mkdir -p /var/www/html/storage
mkdir -p /var/www/html/logs

# Set permissions
chown -R www-data:www-data /var/www/html/database
chmod -R 775 /var/www/html/database
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Initialize database if it doesn't exist
DB_PATH="${DB_PATH:-/var/www/html/database/dtz_production.db}"

if [ ! -f "$DB_PATH" ]; then
    echo "📦 Initializing database..."
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH"
    chmod 664 "$DB_PATH"
    
    # Run database migrations if the script exists
    if [ -f "/var/www/html/tools/init-db.php" ]; then
        echo "🔄 Running database migrations..."
        cd /var/www/html && php tools/init-db.php || echo "⚠️ Migration script failed, continuing anyway"
    fi
    
    echo "✅ Database initialized"
else
    echo "✅ Database already exists"
fi

# Check if JWT_SECRET is set
if [ -z "$JWT_SECRET" ] || [ "$JWT_SECRET" = "change-me-in-production" ]; then
    echo "⚠️ WARNING: JWT_SECRET is not set properly!"
    echo "⚠️ Please set a secure JWT_SECRET environment variable"
fi

echo "🌐 Starting web server on port 8080..."

# Start Apache
exec apache2-foreground
