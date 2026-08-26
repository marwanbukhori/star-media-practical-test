#!/bin/sh
set -e

# Railway assigns a random port via $PORT and expects the container to listen on it;
# Apache's image defaults to a fixed 80. docker-compose never sets $PORT, so this is a
# no-op locally and Apache keeps listening on 80 (mapped to 8000 on the host).
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Belt-and-braces: force a single MPM at container start, not just at build time.
# Railway's deployed container has been observed booting with both mpm_prefork and
# mpm_event enabled in mods-enabled even though a fresh build of this same image only
# ever has mpm_prefork on disk right after `docker build` — some layer/caching behavior
# on Railway's side reintroduces mpm_event before the container's first boot. Doing this
# here, on every start, is unaffected by whatever causes that and is a no-op locally.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec apache2-foreground
