
FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install -j$(nproc) intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libxpm-dev \
    fcgiwrap \
    libmcrypt-dev \
    libwebp-dev \
    libxml2-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    bash \
    curl \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath opcache sockets

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./ ./

COPY ./database ./database

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www

RUN chmod -R 755 /var/www/storage

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache


EXPOSE 9000


CMD ["php-fpm"]

