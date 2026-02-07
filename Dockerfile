FROM php:8.2-cli

# Install FFmpeg
RUN apt-get update && apt-get install -y ffmpeg \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy app files
COPY . /app

# Permissions
RUN chmod -R 777 /app/storage

EXPOSE 3000

CMD ["php", "-S", "0.0.0.0:3000", "-t", "/app"]
