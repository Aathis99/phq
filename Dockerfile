FROM php:8.4-fpm

# ติดตั้ง dependencies และ PHP Extensions ที่ระบุในระบบใหม่
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-install mysqli pdo_mysql curl mbstring

# ตั้งค่า Web Root
WORKDIR /var/www/html
