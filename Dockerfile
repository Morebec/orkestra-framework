# ======================================================================================================================
# BASE
# ======================================================================================================================
FROM alpine:edge as php_base

RUN apk update && apk upgrade

# Install Essentials
RUN apk add --no-cache \
    zip \
    unzip \
    curl

# Install bash
RUN apk add bash
RUN sed -i 's/bin\/ash/bin\/bash/g' /etc/passwd

# Install PHP
RUN apk add --no-cache \
    php7 \
    php7-common \
    php7-gd \
    php7-zlib \
    php7-curl \
    php7-openssl \
    php7-pear \
    php7-pcntl \
    php7-mbstring \
    php7-pgsql \
    php7-pdo_pgsql \
    php7-bcmath \
    php7-xml \
    php7-intl \
    php7-zip \
    php7-opcache \
    php7-phar \
    php7-json \
    php7-ctype \
    php7-iconv \
    php7-session \
    php7-dom \
    php7-tokenizer

# Install Composer
# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN composer self-update --stable

# Install Supervisor
RUN apk add --no-cache python3-dev python3 py3-setuptools
RUN apk add --no-cache supervisor
RUN mkdir -p /etc/supervisor.d/
COPY docker/supervisor.ini /etc/supervisor.d/supervisor.ini


# Set timezone
RUN echo "UTC" > /etc/timezone
RUN date

COPY docker/php.ini /etc/php7/conf.d/php.ini

# Set Working directory
WORKDIR /srv/app

# ======================================================================================================================
# PRODUCTION
# ======================================================================================================================
FROM php_base AS php_prod

# Prepare application files
COPY . .

# Install composer dependencies
RUN composer install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction; \
        composer dump-autoload --classmap-authoritative --no-dev; \
        composer symfony:dump-env prod; \
        composer run-script --no-dev post-install-cmd; \
        chmod +x bin/console; sync

# ======================================================================================================================
# DEVELOPMENT
# ======================================================================================================================
FROM php_base AS php_dev
# Install XDEBUG
RUN apk add --no-cache php7-dev gcc g++ make php7-pecl-xdebug

RUN echo "xdebug.remote_enable=1" >> /etc/php7/conf.d/php-debug.ini && \
    echo "zend_extension=\"$(find / -name xdebug.so)\"" >> /etc/php7/conf.d/php-debug.ini

ENV PHP_IDE_CONFIG 'serverName=DockerApp'


# Run Supervisord
CMD ["supervisord", "-c", "/etc/supervisor.d/supervisor.ini"]