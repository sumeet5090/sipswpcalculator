FROM php:8.2-apache

# Enable ALL modules required by your .htaccess
RUN a2enmod rewrite headers expires deflate

# Install SQLite PDO drivers
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

WORKDIR /var/www/html

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html