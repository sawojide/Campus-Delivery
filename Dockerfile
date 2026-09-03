FROM php:8.2-apache

# 1. Install system libraries needed for SQLite
RUN apt-get update && apt-get install -y libsqlite3-dev

# 2. Install the PHP SQLite extension
RUN docker-php-ext-install pdo_sqlite

# 3. Enable Apache URL rewriting
RUN a2enmod rewrite

# 4. Copy all your files to the website folder
COPY . /var/www/html/

# 5. Set permissions so the website can read/write files (crucial for SQLite)
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 777 /var/www/html

# 6. Tell Render which port to use
EXPOSE 80