ARG PHP_VERSION=8.1

FROM php:${PHP_VERSION}-fpm-alpine AS base

ENV IPE_GD_WITHOUTAVIF=1
ENV COMPOSER_ALLOW_SUPERUSER=1

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql intl zip bcmath redis apcu sockets pcntl gd @composer && \
    docker-php-ext-enable opcache && \
	ln -s $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

CMD ["php-fpm", "--nodaemonize"]
