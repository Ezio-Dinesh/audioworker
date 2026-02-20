FROM php:8.2-fpm

# Install FFmpeg + tools
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP limits
RUN echo "upload_max_filesize=200M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=200M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=0" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

COPY process.php /app/process.php
COPY upload.php /app/upload.php
COPY deletes.php /app/deletes.php

# Storage folders
RUN mkdir -p storage/input storage/output storage/tmp \
 && chmod -R 777 storage

EXPOSE 9000
