FROM php:8.2-cli

# Install ekstensi wajib CI4 (intl, mysqli, pdo_mysql, zip)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install intl pdo pdo_mysql mysqli zip

WORKDIR /app

COPY . /app

CMD php -S 0.0.0.0:$PORT -t public
