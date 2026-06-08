FROM php:8.2-apache

# PHP-Extension curl für API-Calls
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev && \
    docker-php-ext-install curl && \
    rm -rf /var/lib/apt/lists/*

# Dateien ins Apache-Root kopieren
COPY . /var/www/html/

# Schreibrechte für JSON-Dateien (für settings.json etc. falls lokal angelegt)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Railway setzt PORT dynamisch – Apache muss auf diesen Port lauschen
CMD bash -c "sed -i \"s/Listen 80/Listen \${PORT:-80}/\" /etc/apache2/ports.conf && \
    sed -i \"s/:80>/:\${PORT:-80}>/\" /etc/apache2/sites-enabled/000-default.conf && \
    apache2-foreground"
