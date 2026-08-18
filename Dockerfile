FROM php:8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      curl \
      libcurl4-openssl-dev \
      libonig-dev \
      libxml2-dev \
      libzip-dev \
      unzip \
      zip \
 && docker-php-ext-install \
      curl \
      mbstring \
      mysqli \
      opcache \
      pdo \
      pdo_mysql \
      session \
      zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

COPY docker-apache.conf /etc/apache2/conf-available/docker-php.conf
RUN a2enconf docker-php \
 && sed -i 's/Listen 80/Listen ${PORT:-80}/' /etc/apache2/ports.conf \
 && sed -i 's/VirtualHost \*:80/VirtualHost *:${PORT:-80}/' /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80
