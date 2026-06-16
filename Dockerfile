FROM php:8.2-apache

RUN apt-get update && apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo pdo_sqlite && \
    a2enmod rewrite

RUN sed -i 's/80/8110/g' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:8110>/g' /etc/apache2/sites-enabled/000-default.conf

COPY public/ /var/www/html/
RUN mkdir -p /var/www/data && chown www-data:www-data /var/www/data
VOLUME ["/var/www/data"]

EXPOSE 8110
