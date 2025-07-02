# WordPress + PHP optimizado para Palafito B2B
FROM wordpress:fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    bash \
    curl \
    wget \
    git \
    unzip \
    zip \
    less \
    mysql-client \
    imagemagick \
    imagemagick-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    autoconf \
    gcc \
    g++ \
    make

# Instalar extensiones PHP necesarias para WooCommerce
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        zip \
        soap \
        bcmath \
        exif \
        mysqli \
        pdo_mysql \
        opcache

# Instalar imagick
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Instalar Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instalar Xdebug para desarrollo
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configurar PHP para desarrollo
COPY docker/wordpress/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/wordpress/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/wp-cli/main/utils/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Crear usuario para desarrollo
RUN addgroup -g 1000 -S developer \
    && adduser -u 1000 -S developer -G developer -h /home/developer -s /bin/bash

# Configurar permisos
RUN chown -R developer:developer /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Exponer puerto PHP-FPM
EXPOSE 9000

# Script de inicio
COPY docker/wordpress/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]