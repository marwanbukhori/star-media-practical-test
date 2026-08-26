#!/bin/sh
set -e

# Railway assigns a random port via $PORT and expects the container to listen on it;
# Apache's image defaults to a fixed 80. docker-compose never sets $PORT, so this is a
# no-op locally and Apache keeps listening on 80 (mapped to 8000 on the host).
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "DEBUG mods-enabled:"; ls -la /etc/apache2/mods-enabled/ | grep -i mpm
echo "DEBUG apachectl -M:"; apachectl -M 2>&1 | grep -i mpm
echo "DEBUG grep LoadModule mpm:"; grep -rn "LoadModule mpm" /etc/apache2/ 2>/dev/null

exec apache2-foreground
