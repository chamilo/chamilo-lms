# Chamilo LMS 2.0 — PHP 8.2 + Apache
# Matches the extension/ini set used by .github/workflows/{phpunit,behat}.yml
FROM php:8.2-apache-bookworm

ARG NODE_MAJOR=22
# Apache's in-container port. It MUST equal the published host port — see the
# "Design notes" section of docker/README.md. Change it here and in the
# `ports:` list of docker-compose.yml, nowhere else.
ARG HTTP_PORT=8380
ENV APP_DIR=/var/www/chamilo \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

# ---------------------------------------------------------------------------
# System packages: build deps for the PHP extensions + runtime tools Chamilo
# shells out to (ffmpeg for php-ffmpeg, graphviz for graphp/graphviz).
# ---------------------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl git gnupg unzip \
        default-mysql-client \
        ffmpeg graphviz \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        zlib1g-dev; \
    rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP extensions.
# Already bundled in php:8.2 — ctype curl dom fileinfo iconv json libxml
# mbstring pdo simplexml xml xmlreader zlib. The rest are compiled here.
# ---------------------------------------------------------------------------
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture -q DEB_HOST_MULTIARCH)"; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        calendar \
        exif \
        gd \
        intl \
        ldap \
        mysqli \
        opcache \
        pdo_mysql \
        soap \
        sockets \
        zip; \
    pecl install apcu redis pcov; \
    docker-php-ext-enable apcu redis; \
    rm -rf /tmp/pear

# pcov is the coverage driver CI uses (`--coverage-clover clover.xml`). Left
# disabled by default because merely loading it slows every CLI run; enable it
# per-command:
#   docker compose exec web php -d extension=pcov.so -d pcov.enabled=1 \
#       bin/phpunit --coverage-text
RUN echo '; pcov is installed but off by default — see docker/php/Dockerfile' \
    > /usr/local/etc/php/conf.d/pcov.ini.disabled

# ---------------------------------------------------------------------------
# Composer + Node/Yarn (webpack encore builds the Vue frontend)
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN set -eux; \
    mkdir -p /etc/apt/keyrings; \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" \
        > /etc/apt/sources.list.d/nodesource.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends nodejs; \
    rm -rf /var/lib/apt/lists/*; \
    corepack enable; \
    node --version; npm --version

# ---------------------------------------------------------------------------
# Apache: listen on HTTP_PORT (see docker/README.md — the in-container port must
# equal the published host port so root_web is identical for the host browser,
# for Behat, and for the Chrome container).
# ---------------------------------------------------------------------------
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-chamilo.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint

RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint; \
    a2enmod rewrite headers expires actions; \
    sed -i "s/^Listen 80$/Listen ${HTTP_PORT}/" /etc/apache2/ports.conf; \
    sed -i "s/__HTTP_PORT__/${HTTP_PORT}/g" /etc/apache2/sites-available/000-default.conf; \
    grep -q "^Listen ${HTTP_PORT}$" /etc/apache2/ports.conf; \
    grep -q "VirtualHost \*:${HTTP_PORT}" /etc/apache2/sites-available/000-default.conf

WORKDIR ${APP_DIR}
EXPOSE ${HTTP_PORT}

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["apache2-foreground"]
