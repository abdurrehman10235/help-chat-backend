FROM node:18-slim

# Set working directory
WORKDIR /var/www

# Install system dependencies for Chrome/Chromium and PHP
RUN apt-get update && apt-get install -y \
    # Chrome dependencies
    wget \
    gnupg \
    ca-certificates \
    procps \
    libxss1 \
    libgconf-2-4 \
    libxrandr2 \
    libasound2 \
    libpangocairo-1.0-0 \
    libatk1.0-0 \
    libcairo-gobject2 \
    libgtk-3-0 \
    libgdk-pixbuf2.0-0 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxi6 \
    libxrender1 \
    libxtst6 \
    libxss1 \
    libglib2.0-0 \
    libnss3 \
    libnspr4 \
    libdbus-1-3 \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxkbcommon0 \
    libatspi2.0-0 \
    # PHP and other dependencies
    curl \
    software-properties-common \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP 8.2
RUN curl -sSL https://packages.sury.org/php/README.txt | bash -x \
    && apt-get update \
    && apt-get install -y \
    php8.2-cli \
    php8.2-zip \
    php8.2-sqlite3 \
    php8.2-pdo \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    sqlite3 \
    unzip \
    git

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Google Chrome Stable
RUN wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list' \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction --no-progress \
    && composer dump-autoload

# Install Node.js dependencies
RUN npm install

# Create supervisor configuration with proper user
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'logfile=/var/log/supervisord.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'pidfile=/var/run/supervisord.pid' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:laravel]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php /var/www/artisan serve --host=0.0.0.0 --port=8000' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/laravel.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/laravel_error.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:whatsapp-bot]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=node /var/www/whatsapp-bot-render.js' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/var/log/whatsapp.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/var/log/whatsapp_error.log' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startretries=5' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'startsecs=10' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stopwaitsecs=30' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'killasgroup=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stopasgroup=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'environment=NODE_ENV=production,BACKEND_URL=http://localhost:8000/api,PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true,PUPPETEER_EXECUTABLE_PATH=/usr/bin/google-chrome-stable' >> /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories and set permissions
RUN mkdir -p /var/www/whatsapp-session && \
    mkdir -p /var/www/storage/app && \
    mkdir -p /var/log && \
    chmod -R 755 /var/www && \
    chmod -R 777 /var/www/whatsapp-session && \
    chmod -R 777 /var/www/storage

# Expose Laravel's default dev port
EXPOSE 8000

# Create startup script
RUN echo '#!/bin/bash' > /var/www/start.sh && \
    echo 'cd /var/www' >> /var/www/start.sh && \
    echo 'echo "🔧 Setting up Laravel..."' >> /var/www/start.sh && \
    echo 'if [ -f .env.production ]; then cp .env.production .env; else cp .env.example .env; fi' >> /var/www/start.sh && \
    echo 'sed -i "s|DB_DATABASE=.*|DB_DATABASE=/var/www/database/database.sqlite|g" .env' >> /var/www/start.sh && \
    echo 'sed -i "s|APP_URL=.*|APP_URL=https://laravel-backend-r3ut.onrender.com|g" .env' >> /var/www/start.sh && \
    echo 'sed -i "s|APP_ENV=.*|APP_ENV=production|g" .env' >> /var/www/start.sh && \
    echo 'sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|g" .env' >> /var/www/start.sh && \
    echo 'php artisan key:generate --force' >> /var/www/start.sh && \
    echo 'php artisan config:clear' >> /var/www/start.sh && \
    echo 'php artisan cache:clear' >> /var/www/start.sh && \
    echo 'php artisan view:clear' >> /var/www/start.sh && \
    echo 'php artisan route:clear' >> /var/www/start.sh && \
    echo 'php artisan migrate --force' >> /var/www/start.sh && \
    echo 'php artisan db:seed --force' >> /var/www/start.sh && \
    echo 'echo "✅ Laravel setup complete"' >> /var/www/start.sh && \
    echo 'echo "Starting services..."' >> /var/www/start.sh && \
    echo 'supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /var/www/start.sh && \
    chmod +x /var/www/start.sh

# Run migrations, seeder, then launch both services with supervisor
CMD ["/var/www/start.sh"]