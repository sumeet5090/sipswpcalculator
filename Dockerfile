FROM php:8.2-apache

# Enable ALL modules required by your .htaccess
RUN a2enmod rewrite headers expires deflate

# Install required dependencies and PHP extensions (SQLite & GD for PDFs)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite gd

WORKDIR /var/www/html

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html