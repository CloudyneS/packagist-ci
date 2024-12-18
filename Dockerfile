FROM php:8.3-cli-alpine

LABEL Version="1.0"
LABEL Maintainer="Cloudyne Systems"
LABEL org.opencontainers.image.source="https://github.com/cloudynes/packagist-ci"
LABEL Description="Container for uploading artifacts to packagist.org/private packagist"
LABEL org.opencontainers.image.description="Container for uploading artifacts to packagist.org/private packagist"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /app

ADD . /app

RUN apk update \
    && apk add --no-cache git libzip-dev gettext curl \
    && docker-php-ext-install zip \
    && docker-php-source delete \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --no-interaction \
    && echo 'memory_limit = 2048M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

CMD ["php", "app.php"]