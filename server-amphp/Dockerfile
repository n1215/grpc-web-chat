FROM php:8.0-cli

RUN apt-get update && apt-get install -y \
     git \
     iputils-ping \
     net-tools \
     zlib1g-dev \
     unzip \
     libzip-dev

RUN docker-php-ext-install pcntl

RUN pecl install grpc && docker-php-ext-enable grpc

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

COPY ./server.php /var/www/server.php

WORKDIR /var/www

CMD ["/usr/local/bin/php", "/var/www/server.php"]
