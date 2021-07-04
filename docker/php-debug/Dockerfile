# ======================================================================================================================
# INTERNAL DEVELOPMENT IMAGE OF PHP.
# Note: the build context should always be the project's root directory.
# ======================================================================================================================
FROM ubuntu:20.04

RUN apt-get update

# Install Essentials
RUN apt-get install -y zip unzip curl
RUN DEBIAN_FRONTEND="noninteractive" TZ=Etc/UTC apt-get -y install tzdata

# Install Git
RUN apt-get install -y git

# Install PHP & XDebug
RUN apt-get install -y \
    php7.4-cli \
    php7.4-dev \
    # Common PHP Modules
    php-common \
    php-gd \
    # php-zlib \
    php-curl \
    # php-openssl \
    php-pear \
    # php-pcntl \
    php-mbstring \
    php-pgsql \
    php-bcmath \
    php-xml \
    php-intl \
    # php-zip \
    php-opcache \
    php-phar \
    php-json \
    php-ctype \
    php-iconv \
    # php-session \
    php-dom \
    php-tokenizer \
    php-posix \
    # php-sodium \
    php-xmlwriter \
    php-sockets

# Install xdebug
RUN mkdir -p /tmp/pear/cache
RUN pecl channel-update pecl.php.net
RUN apt-get install php-pear

RUN find -name phpize

RUN pecl install xdebug
RUN phpenmod xdebug


RUN php -m

# COPY docker/php/php.ini /etc/php/7.4/cli/conf.d/php.ini
COPY docker/php-debug/php-debug.ini /etc/php/7.4/cli/conf.d/php-debug.ini

# Set Working directory
WORKDIR /srv/app

COPY . .

# Install Composer
# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_IDE_CONFIG="serverName=DockerApp"
RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
# RUN composer --version
# RUN composer self-update --stable
# RUN composer install

# Install Supervisor
RUN apt-get install -y supervisor
RUN mkdir -p /etc/supervisor.d/
COPY docker/supervisor.ini /etc/supervisor.d/supervisor.ini

# Run Supervisord
CMD ["supervisord", "-c", "/etc/supervisor.d/supervisor.ini"]
