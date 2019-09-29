FROM php:7.1.8-apache


COPY . /src/app
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /src/app

RUN docker-php-ext-install mbstring pdo pdo_mysql \
    && chown -R www-data:www-data /src/app