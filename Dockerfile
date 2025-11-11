# Dockerfile

FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    unzip \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libpng-dev \
    zlib1g-dev \
    libjpeg-dev \
    libicu-dev \
    libfreetype6-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy custom php.ini
COPY ./conf/php.ini /usr/local/etc/php/

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY ./app /var/www/html

# Create necessary directories if they don't exist
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

CMD ["php-fpm"]
