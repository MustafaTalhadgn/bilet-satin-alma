FROM php:8.2-apache

# Gerekli bağımlılıkları kur
RUN apt-get update && apt-get install -y \
    libsqlite3-dev zip unzip git && \
    docker-php-ext-install pdo pdo_sqlite

# Apache ayarlarını yap
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

EXPOSE 80
