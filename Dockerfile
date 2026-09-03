FROM php:8.2-apache

# Copy all your project files into Apache's default web directory
COPY . /var/www/html/

# Enable Apache mod_rewrite (required if you use .htaccess for routing)
RUN a2enmod rewrite

# Apache listens on port 80. Render will automatically map this to its internal $PORT
EXPOSE 80