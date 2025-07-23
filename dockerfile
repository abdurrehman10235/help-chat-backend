FROM php:8.2-cli

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
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_sqlite zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-progress \
    && composer dump-autoload

# Install Node.js dependencies
RUN npm install

# Create supervisor configuration
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:laravel]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php /var/www/artisan serve --host=0.0.0.0 --port=8000' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/laravel.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/laravel_error.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:whatsapp-bot]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=node /var/www/whatsapp-bot-web.js' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/whatsapp.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/whatsapp_error.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'environment=NODE_ENV=production,BACKEND_URL=http://localhost:8000/api' >> /etc/supervisor/conf.d/supervisord.conf

# Laravel needs write permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Create storage directories for WhatsApp session and status
RUN mkdir -p /var/www/whatsapp-session && \
    mkdir -p /var/www/storage/app && \
    chown -R www-data:www-data /var/www/whatsapp-session && \
    chown -R www-data:www-data /var/www/storage

# Expose Laravel's default dev port
EXPOSE 8000

# Create startup script
RUN echo '#!/bin/bash' > /var/www/start.sh && \
    echo 'php artisan migrate --force' >> /var/www/start.sh && \
    echo 'php artisan db:seed --force' >> /var/www/start.sh && \
    echo 'supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /var/www/start.sh && \
    chmod +x /var/www/start.sh

# Run migrations, seeder, then launch both services with supervisor
CMD ["/var/www/start.sh"]