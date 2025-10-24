FROM php:8.2-apache


RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    zip unzip git && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite iconv intl


ENV APACHE_DOCUMENT_ROOT=/var/www/html/public


RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite headers && \
    sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html


COPY src/ .


RUN mkdir -p /var/www/html/storage \
    && mkdir -p /var/www/html/public/assets/images/logos \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/public/assets/images/logos \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/public/assets/images/logos


    
EXPOSE 80