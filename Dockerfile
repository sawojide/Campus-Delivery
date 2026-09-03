FROM php:8.2-apache

# Install SQLite extension
RUN docker-php-ext-install pdo_sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Give write permissions for SQLite database file
RUN chown -R www-data:www-data /var/www/html/
RUN chmod 777 /var/www/html/

EXPOSE 80