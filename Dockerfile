FROM php:8.2-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql mysqli zip

# Active mod_rewrite pour tes fichiers .htaccess
RUN a2enmod rewrite

# Extension MongoDB (si nosql_db.php l'utilise réellement)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer (au cas où tu dois réinstaller les dépendances)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html