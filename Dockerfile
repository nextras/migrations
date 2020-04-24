FROM library/php:7.4-fpm-alpine

# Composer & hirak/prestissimo
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer global require hirak/prestissimo

# Install PostgreSQL PHP drivers
RUN apk add --no-cache \
        postgresql-dev \
        postgresql-client \
    && \
    docker-php-ext-install \
        pgsql \
        pdo_pgsql \
    && \
    apk del postgresql-dev \
&& :

# Install MySQL PHP drivers
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
&& :

# Install base unix tools
RUN apk add --no-cache \
    bash \
    vim \
    coreutils \
    grep \
&& :
