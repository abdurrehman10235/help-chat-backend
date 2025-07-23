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
    php8.2-xml php8.2-curl php8.2-bcmath php8.2-gd php8.2-intl \
    php8.2-tokenizer php8.2-fileinfo sqlite3 unzip \
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

# Install dependencies and setup during build (not runtime)
RUN composer install --optimize-autoloader --no-interaction \
    && npm install \
    && cp .env.example .env \
    && php artisan key:generate --force

# Create directories
RUN mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache whatsapp-session && \
    chmod -R 777 storage whatsapp-session && \
    chmod -R 775 bootstrap/cache

EXPOSE 8000

# Startup script (simplified - only run what changes between deployments)
RUN echo '#!/bin/bash' > start.sh && \
    echo 'echo "Starting application..."' >> start.sh && \
    echo 'echo "PORT: ${PORT:-8000}"' >> start.sh && \
    echo 'php artisan migrate --force || echo "Migration failed, continuing..."' >> start.sh && \
    echo 'php artisan db:seed --force || echo "Seeding failed, continuing..."' >> start.sh && \
    echo 'echo "Starting WhatsApp bot in background..."' >> start.sh && \
    echo 'node whatsapp-bot.js &' >> start.sh && \
    echo 'echo "Starting Laravel server on port ${PORT:-8000}..."' >> start.sh && \
    echo 'php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' >> start.sh && \
    chmod +x start.sh

CMD ["./start.sh"]