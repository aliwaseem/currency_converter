# Dockerfile for the Currency Converter application
# This file defines the services, networks, and volumes for the application.
######################################
# 1. Base Image
######################################
FROM php:8.2-apache

######################################
# 2. Install System Dependencies & PHP Extensions
######################################
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-install \
       mbstring \
       zip \
       intl \
       opcache \
       pdo_mysql \
       mysqli \
       bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

######################################
# 3. Install Composer Globally
######################################
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

######################################
# 4. Install Symfony CLI
######################################
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    -s -- --install-dir=/usr/local/bin

######################################
# 5. Set Working Directory
######################################
WORKDIR /srv/app

######################################
# 6. Create var/ Directory & Set Permissions
######################################
RUN mkdir -p /srv/app/var \
    && chown -R www-data:www-data /srv/app/var

######################################
# 7. Configure Apache Document Root and Enable mod_rewrite
######################################
ENV APACHE_DOCUMENT_ROOT /srv/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

######################################
# 8. Create Apache Virtual Host Configuration
######################################
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

######################################
# 9. Expose Port
######################################
EXPOSE 80

######################################
# 10. Start Apache in foreground
######################################
CMD ["apache2-foreground"]
