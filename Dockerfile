FROM php:8.2-fpm

# Install FFmpeg + tools
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Make PHP-FPM reachable from outside
RUN sed -i 's|listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf

# PHP limits
RUN echo "upload_max_filesize=200M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=200M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=0" >> /usr/local/etc/php/conf.d/uploads.ini

# 🔥 THIS PATH IS REQUIRED FOR COOLIFY
WORKDIR /var/www/html

COPY process.php .
COPY upload.php .
COPY deletes.php .

RUN mkdir -p storage/input storage/output storage/tmp \
 && chmod -R 777 storage

EXPOSE 9000
