FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates \
      curl \
      libcurl4-openssl-dev \
      libonig-dev \
      libxml2-dev \
      libzip-dev \
      unzip \
      zip \
 && docker-php-ext-install \
      mbstring \
      mysqli \
      opcache \
      pdo_mysql \
      zip \
 && a2enmod rewrite actions alias \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY docker-apache.conf /etc/apache2/conf-available/docker-php.conf
RUN a2enconf docker-php \
 && sed -i -E 's/Listen[[:space:]]+80/Listen ${PORT:-80}/g' /etc/apache2/ports.conf \
 && sed -i -E 's/VirtualHost[[:space:]]+\*:80/VirtualHost *:${PORT:-80}/g' /etc/apache2/sites-enabled/000-default.conf \
 && { \
      echo '#!/bin/sh'; \
      echo 'set -e'; \
      echo 'export APACHE_ARGUMENTS="-D FOREGROUND"'; \
      echo 'exec apache2-foreground'; \
    } > /usr/local/bin/start.sh \
 && chmod +x /usr/local/bin/start.sh

WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html/

RUN find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80

CMD ["start.sh"]
