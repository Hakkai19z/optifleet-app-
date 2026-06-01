FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    zip \
    openssl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        intl \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json ./

RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

RUN mkdir -p var/cache var/log config/jwt && \
    chmod -R 777 var/ && \
    if [ ! -f config/jwt/private.pem ]; then \
        openssl genrsa -out config/jwt/private.pem -aes256 \
            -passout pass:optifleet_jwt_passphrase 4096 && \
        openssl rsa -pubout \
            -in config/jwt/private.pem \
            -out config/jwt/public.pem \
            -passin pass:optifleet_jwt_passphrase; \
    fi

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/", "public/index.php"]
