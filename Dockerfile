FROM php:8.2-fpm

# Install FFmpeg
RUN apt-get update && apt-get install -y \
    ffmpeg git unzip \
    && rm -rf /var/lib/apt/lists/*

# Make FPM listen on TCP (required for Coolify)
RUN sed -i 's|listen = .*|listen = 0.0.0.0:8000|' /usr/local/etc/php-fpm.d/www.conf \
 && sed -i 's|;listen.mode = 0660|listen.mode = 0666|' /usr/local/etc/php-fpm.d/www.conf

# PHP limits
RUN echo "upload_max_filesize=200M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=200M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=0" >> /usr/local/etc/php/conf.d/uploads.ini

# REQUIRED path for Coolify
WORKDIR /var/www/html

COPY process.php .
COPY upload.php .
COPY deletes.php .

RUN mkdir -p storage/input storage/output storage/tmp \
 && chmod -R 777 storage

EXPOSE 8000

CMD ["php-fpm"]
