FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-progress

# Laravel needs write permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Generate .env and app key if needed
# Optional: RUN cp .env.example .env && php artisan key:generate

# Expose port
EXPOSE 8000

# Start Laravel server
CMD touch database.sqlite && php artisan migrate --force && php artisan db:seed && php artisan serve --host=0.0.0.0 --port=8000