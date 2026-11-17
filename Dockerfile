FROM php:8.4.27-fpm

WORKDIR /var/www
ARG MAX_CHILDREN=40

# FIX: Upgrade it if it is installed and a patched version is available.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    && if dpkg-query -W -f='${Status}' linux-libc-dev 2>/dev/null | grep -q 'install ok installed'; then apt-get install -y --only-upgrade linux-libc-dev; fi \
    && docker-php-ext-install -j"$(nproc)" intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN sed -i "s/^pm\.max_children\s*=.*/pm.max_children = ${MAX_CHILDREN}/" /usr/local/etc/php-fpm.d/www.conf

RUN apt-get update && apt-get install -y --no-install-recommends \
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
    && docker-php-ext-install -j"$(nproc)" gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath opcache sockets

# FIX 3: Use a stable Composer major version instead of latest.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini

COPY ./ ./

RUN chmod +x ./start.sh

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000

CMD ["./start.sh"]
