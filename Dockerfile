FROM php:7.3-fpm-alpine

# Add composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Add app
COPY . /app
WORKDIR /app

RUN composer -n install -o --no-dev

CMD ["php-fpm"]