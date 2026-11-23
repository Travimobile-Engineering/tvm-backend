FROM php:8.4.27-fpm

WORKDIR /var/www
ARG MAX_CHILDREN=40

# FIX: upgrade ALL OS packages (krb5 libs, linux-libc-dev, ...), not just linux-libc-dev.
RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    libicu-dev \
    && docker-php-ext-install -j"$(nproc)" intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# FIX: plain path (the pasted line had a markdown link around www.conf).
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

# All PHP extensions are compiled in this RUN.
# FIX: once compiling is finished, purge linux-libc-dev (kernel headers, ~40 of the typical
# findings). Purging it also removes libc6-dev and the compilers (build-essential, gcc, make),
# which PHP-FPM does not need at runtime. Runtime libraries are kept (no --auto-remove).
# If a LATER step ever needs a compiler, move this purge after that step.
RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath opcache sockets \
    && apt-get purge -y linux-libc-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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
