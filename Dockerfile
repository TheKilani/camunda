FROM php:8.2-cli

# Enable cURL + SQLite (PDO SQLite)
RUN apt-get update \
  && apt-get install -y --no-install-recommends libcurl4-openssl-dev libsqlite3-dev \
  && docker-php-ext-install curl pdo_sqlite \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

RUN mkdir -p /app/data

ENV APP_DB_PATH=/app/data/app.sqlite
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public", "/app/public/router.php"]
