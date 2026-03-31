FROM php:8.3-cli

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    libicu-dev \
    libgd-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        zip \
        bcmath \
        curl \
        intl \
        gd \
        pcntl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy and install PHP dependencies
COPY composer.json composer.lock ./
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

# Copy and install Node dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy everything else and build
COPY . .

RUN COMPOSER_MEMORY_LIMIT=-1 composer dump-autoload --optimize \
    && npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8080

CMD php artisan migrate --force && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
