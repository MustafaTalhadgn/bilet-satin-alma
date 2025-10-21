# Temel imaj olarak PHP 8.2 ve Apache sunucusu kullan
FROM php:8.2-apache

# Gerekli sistem bağımlılıklarını kur
# DÜZELTME: 'intl' eklentisi için 'libicu-dev' eklendi
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    zip unzip git && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite iconv intl

# Apache'nin doküman kök dizinini /public olarak ayarla
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Apache yapılandırma dosyalarını yeni kök dizine göre güncelle
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Çalışma dizinini ayarla
WORKDIR /var/www/html

# Proje kök dizinindeki 'src' klasörünün içeriğini container'ın çalışma dizinine kopyala
COPY src/ .

# İzinleri ayarla
RUN mkdir -p /var/www/html/storage \
    && mkdir -p /var/www/html/public/assets/images/logos \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/public/assets/images/logos \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/public/assets/images/logos

# 80 portunu dışarıya aç
EXPOSE 80