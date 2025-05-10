# Use PHP 8.2 with Alpine for a lightweight image
FROM php:8.2-fpm-alpine

# Set working directory in the container
WORKDIR /var/www/html

# Install required dependencies and PHP extensions
RUN apk add --no-cache \
    zip unzip curl \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    bash \
    # Install Node.js (latest LTS version) and npm
    nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql bcmath

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the Laravel application files into the container
COPY . /var/www/html

# Set the proper permissions for the Laravel application
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Set the default command to start PHP-FPM
CMD ["php-fpm"]
