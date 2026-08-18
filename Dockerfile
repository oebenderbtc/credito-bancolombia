FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html
ENV PORT=80

RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      ca-certificates \
      curl \
      libcurl4-openssl-dev \
      libonig-dev \
      libxml2-dev \
      libzip-dev \
      unzip \
      zip; \
    docker-php-ext-install \
      mbstring \
      mysqli \
      opcache \
      pdo_mysql \
      zip; \
    a2enmod rewrite actions alias expires headers; \
    a2enconf docker-php || true; \
    apt-mark auto '.*' > /dev/null; \
    [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*; \
    # Render asigna PORT dinámicamente; aseguramos que Apache escuche en $PORT
    { \
      echo 'ServerName localhost'; \
      echo 'Listen 0.0.0.0:${PORT}'; \
    } | tee /etc/apache2/ports.conf; \
    rm -f /etc/apache2/sites-enabled/*.conf; \
    { \
      echo '<VirtualHost *:${PORT}>'; \
      echo '  DocumentRoot ${APACHE_DOCUMENT_ROOT}'; \
      echo '  <Directory ${APACHE_DOCUMENT_ROOT}>'; \
      echo '    Options -Indexes +FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '  </Directory>'; \
      echo '  DirectoryIndex index.html index.php'; \
      echo '  LogLevel warn'; \
      echo '  ErrorLog /dev/stderr'; \
      echo '  CustomLog /dev/stdout combined'; \
      echo '</VirtualHost>'; \
    } | tee /etc/apache2/sites-enabled/000-default.conf

COPY docker-apache.conf /etc/apache2/conf-available/docker-php.conf
RUN a2enconf docker-php || true

WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html /var/log/apache2 /var/run/apache2 /var/lock/apache2

EXPOSE 80

CMD ["apache2-foreground"]
