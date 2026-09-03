FROM php:8.2-apache

# Install MySQL PDO extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80