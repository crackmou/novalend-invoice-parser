FROM php:8.2-fpm

# Installer les dépendances nécessaires pour PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    curl \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Relève la limite mémoire de PHP (parsing de gros fichiers)
RUN echo "memory_limit = 1024M" > /usr/local/etc/php/conf.d/app-memory.ini

# Driver de couverture de code (PCOV : bien plus léger que Xdebug pour le coverage)
RUN apt-get update && apt-get install -y $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Nettoyer le cache apt pour alléger l'image
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
