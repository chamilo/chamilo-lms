FROM composer:latest as composer

FROM php:8-apache
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_14.x | bash - \
    && apt-get install -y \ 
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \ 
        libcurl4-openssl-dev \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libxslt-dev \ 
        curl \
        git \
        libapache2-mod-xsendfile \
        unzip \
        nodejs && npm install -g yarn

RUN pecl install apcu \
    && docker-php-ext-enable apcu \
    && echo 'memory_limit = -1' >> $PHP_INI_DIR/conf.d/docker-php-ram-limit.ini

RUN docker-php-ext-configure gd --with-freetype --with-jpeg  \
    && docker-php-ext-install -j$(nproc) bcmath curl gd intl  pdo_mysql zip soap xml mbstring xsl exif \
    && a2enmod rewrite ssl headers expires && apache2ctl restart 

RUN rm $PHP_INI_DIR/conf.d/docker-php-ram-limit.ini \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN mkdir chamilo2 && chown -R www-data:www-data /var/www
USER www-data
RUN git clone https://github.com/chamilo/chamilo-lms.git chamilo2 \
    && cd chamilo2 && composer install -n
RUN cd chamilo2 && yarn set version 2.4.2 &&  yarn install && yarn run encore dev && chmod -R 777 .
COPY 000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf