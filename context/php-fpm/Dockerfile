FROM php:8.1-fpm-alpine

RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli

RUN apk update && \
  curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/html
COPY composer.json /var/www/html
RUN composer install