FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN a2enmod headers

RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

RUN useradd -m -s /bin/bash appuser \
    && chown -R appuser:appuser /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod 755 /var/www/html/db \
    && chmod 644 /var/www/html/db/database.sqlite
USER appuser
RUN mkdir -p /var/www/html/db && \
if [ ! -f /var/www/html/db/database.sqlite ]; then \
    touch /var/www/html/db/database.sqlite && \
    sqlite3 /var/www/html/db/database.sqlite < /var/www/html/db/init.sql || true; \
fi

EXPOSE 80

CMD ["apache2-foreground"]
