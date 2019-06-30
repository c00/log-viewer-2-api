FROM php:7.3-fpm-alpine

# Add the basic extensions
RUN docker-php-ext-install -j$(nproc) pdo_mysql mysqli mbstring opcache

# Add composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Add app
COPY . /app
WORKDIR /app

RUN composer -n install -o --no-dev

CMD ["php-fpm"]