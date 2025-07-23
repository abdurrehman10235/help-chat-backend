FROM node:18-slim

WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    wget gnupg ca-certificates curl \
    libxss1 libasound2 libpangocairo-1.0-0 libatk1.0-0 libcairo-gobject2 \
    libgtk-3-0 libgdk-pixbuf2.0-0 libnss3 libxrandr2 libxcomposite1 \
    libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrender1 \
    libxtst6 libglib2.0-0 libnspr4 libdbus-1-3 libatk-bridge2.0-0 \
    libdrm2 libxkbcommon0 libatspi2.0-0 \
    php8.2-cli php8.2-zip php8.2-sqlite3 php8.2-pdo php8.2-mbstring \
    php8.2-xml php8.2-curl sqlite3 unzip supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Chrome
RUN wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google.list \
    && apt-get update && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Copy project files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-interaction \
    && npm install

# Setup supervisor
RUN echo '[supervisord]' > /etc/supervisor/conf.d/app.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/app.conf && \
    echo '[program:laravel]' >> /etc/supervisor/conf.d/app.conf && \
    echo 'command=php artisan serve --host=0.0.0.0 --port=8000' >> /etc/supervisor/conf.d/app.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo '[program:whatsapp]' >> /etc/supervisor/conf.d/app.conf && \
    echo 'command=node whatsapp-bot.js' >> /etc/supervisor/conf.d/app.conf && \
    echo 'directory=/var/www' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/app.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/app.conf

# Create directories
RUN mkdir -p storage/app whatsapp-session && chmod -R 777 storage whatsapp-session

EXPOSE 8000

# Startup script
RUN echo '#!/bin/bash' > start.sh && \
    echo 'cp .env.example .env' >> start.sh && \
    echo 'php artisan key:generate --force' >> start.sh && \
    echo 'php artisan migrate --force' >> start.sh && \
    echo 'php artisan db:seed --force' >> start.sh && \
    echo 'supervisord -c /etc/supervisor/conf.d/app.conf' >> start.sh && \
    chmod +x start.sh

CMD ["./start.sh"]