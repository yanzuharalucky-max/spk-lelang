FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app

COPY . /app

RUN mkdir -p /app/tmp_sessions /app/assets/uploads \
    && chmod -R 777 /app/tmp_sessions /app/assets/uploads

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]