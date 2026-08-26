#!/bin/sh
set -e

# Railway assigns a random port via $PORT and expects the container to listen on it;
# Apache's image defaults to a fixed 80. docker-compose never sets $PORT, so this is a
# no-op locally and Apache keeps listening on 80 (mapped to 8000 on the host).
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
