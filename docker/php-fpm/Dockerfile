# ======================================================================================================================
# BASE
# ======================================================================================================================
FROM php:7.4-fpm as php_base

RUN apt-get update

RUN apt-get install -y \
    libzip-dev \
    zip \
    libpq-dev \
    git \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    librabbitmq-dev

RUN pecl install mongodb && \
    docker-php-ext-enable mongodb

RUN docker-php-ext-configure intl && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo pdo_pgsql && \
    docker-php-ext-install pcntl && \
    docker-php-ext-install zip xml gd

RUN docker-php-ext-install opcache

# Install Composer
# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN composer self-update --stable

# Set timezone
RUN rm /etc/localtime
RUN ln -s /usr/share/zoneinfo/${PHP_DATE_TIMEZONE:-UTC} /etc/localtime
RUN date

WORKDIR /srv/app

# Create log and cache directories
RUN if [ -f var/log ]; then \
    mkdir var/log; \
  fi;

RUN if [ -f var/log ]; then \
    mkdir var/cache; \
  fi;


# ======================================================================================================================
# PRODUCTION
# ======================================================================================================================
FROM php_base AS php_prod

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
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug && \
    echo "xdebug.remote_enable=1" >> /usr/local/etc/php/php.ini && \
    echo "zend_extension=\"$(find / -name xdebug.so)\"" >> /usr/local/etc/php/php.ini

ENV PHP_IDE_CONFIG 'serverName=DockerApp'



CMD ["php-fpm"]