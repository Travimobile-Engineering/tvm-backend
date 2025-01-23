
# FROM php:8.3.11-fpm AS build

# WORKDIR /app

# # update package list and Install dependencies
# RUN apt-get update && apt-get install -y \
#     build-essential \
#     libpng-dev \
#     libjpeg62-turbo-dev \
#     libfreetype6-dev \
#     libwebp-dev \
#     libxpm-dev \
#     locales \
#     zip \
#     jpegoptim optipng pngquant gifsicle \
#     vim \
#     unzip \
#     git \
#     bash \
#     fcgiwrap \
#     libmcrypt-dev \
#     curl \
#     libonig-dev \
#     libxml2-dev \
#     libzip-dev \
#     libpq-dev \
#     zip \
#     && apt-get clean && rm -rf /var/lib/apt/lists/*

# # Install PHP extensions
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
#     && docker-php-ext-install gd \
#     && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# # Install Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# COPY . /app

# RUN composer install --no-dev --optimize-autoloader

# # Stage 2: Production stage
# FROM php:8.2-apache

# RUN a2enmod rewrite

# WORKDIR /var/www/html

# COPY --from=build /app /var/www/html

# COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

# RUN [ -f /var/www/html/public/index.php ] || (echo "index.php is missing" && exit 1)

# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# EXPOSE 80

# CMD ["apache2-foreground"]


FROM php:8.2-fpm-bullseye

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

RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath opcache

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www

RUN chmod -R 755 /var/www/storage

RUN apt-get update && apt-get install -y \
    nginx \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./server/default.conf /etc/nginx/conf.d/default.conf

RUN apt-get update && apt-get install -y supervisor \
    && mkdir -p /var/log/supervisor

COPY ./server/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

