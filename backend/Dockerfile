FROM php:8.2-cli

# Install dependencies required by Laravel and Composer
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    git \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install zip pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Copy the application code
COPY . .

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Setup Environment and Database automatically
RUN cp .env.example .env && \
    php artisan key:generate && \
    touch database/database.sqlite && \
    php artisan migrate --force

# Set proper permissions
RUN chown -R www-data:www-data storage bootstrap/cache database

# Expose port 8000 for Render
EXPOSE 8000

# Start the built-in PHP server
CMD php artisan serve --host=0.0.0.0 --port=8000
