FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Enable error logging
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php.ini

# Copy code to Apache directory
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
