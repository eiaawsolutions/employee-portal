#!/usr/bin/env bash
# EIAAW Workforce — Railway boot script.
#
# Boot sequence (idempotent on healthy DB; self-heals from partial state):
#   1. Inspect DB: tenants table present means DB is healthy.
#   2. Inspect DB: migrations table without tenants means a previous boot
#      failed partway. Drop+recreate the public schema so migrations can
#      run cleanly.
#   3. `php artisan migrate --force` runs every migration from scratch on
#      the clean DB. The migrations themselves bootstrap the Claritas-era
#      tables + the SaaS retrofit on top.
#   4. Start the scheduler + queue worker, then nginx + php-fpm.
#
# We deliberately DON'T use the pgsql-schema.sql baseline dump — it was
# generated when Cashier migrations had old timestamps, and renaming
# them for correct order conflicts with the dump's recorded migration
# names. Easier to run all migrations from empty DB; takes ~10 seconds.

set -euo pipefail

if [[ -z "${DATABASE_URL:-}" ]]; then
    echo "FATAL: DATABASE_URL not set" >&2
    exit 1
fi

has_table() {
    local name="$1"
    psql "$DATABASE_URL" -tAc \
        "SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='$name'" \
        2>/dev/null | grep -q 1
}

# FORCE_WIPE_DB=true environment override — wipes the public schema
# unconditionally before migrating. Use during early deploys when the
# migration-order is still being stabilised. REMOVE this env var after
# first successful production deploy to prevent accidental data loss.
if [[ "${FORCE_WIPE_DB:-false}" == "true" ]]; then
    echo "=== FORCE_WIPE_DB=true — wiping public schema ==="
    psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -c \
        "DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres; GRANT ALL ON SCHEMA public TO public;"
elif has_table tenants; then
    echo "=== DB healthy (tenants table present) ==="
elif has_table migrations; then
    echo "=== DB in partial state — wiping public schema for clean migration run ==="
    psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -c \
        "DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres; GRANT ALL ON SCHEMA public TO public;"
fi

echo "=== Running migrations ==="
php artisan migrate --force

PORT="${PORT:-8080}"

# Background processes the app depends on: the scheduler (start-date
# activation, trial end, reminders, backups, retention) and the queue worker.
# Each restarts itself if it exits; logs go to the container's stdout.
supervise() {
    local name="$1"; shift
    ( while true; do
        "$@" || echo "=== ${name} exited ($?); restarting in 5s ===" >&2
        sleep 5
      done ) &
    echo "=== ${name} started (pid $!) ==="
}
supervise scheduler php artisan schedule:work
supervise queue php artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-time=3600

# Web server: nginx + php-fpm. If either can't start, fall back to PHP's
# built-in server so a bad web config never takes the site down.
# Nix builds of nginx report --prefix rather than --conf-path; mime.types and
# fastcgi_params live in <prefix>/conf.
NGINX_PREFIX="$(nginx -V 2>&1 | grep -o -- '--prefix=[^ ]*' | cut -d= -f2 || true)"
NGINX_CONF_DIR="${NGINX_PREFIX:+${NGINX_PREFIX}/conf}"
if [[ -z "${NGINX_CONF_DIR}" ]]; then
    NGINX_CONF_DIR="$(dirname "$(nginx -V 2>&1 | grep -o -- '--conf-path=[^ ]*' | cut -d= -f2)" 2>/dev/null || true)"
fi
if command -v nginx >/dev/null && command -v php-fpm >/dev/null && [[ -f "${NGINX_CONF_DIR}/mime.types" ]]; then
    mkdir -p /tmp/nginx-body /tmp/nginx-fastcgi /tmp/nginx-proxy /tmp/nginx-uwsgi /tmp/nginx-scgi
    sed -e "s|__PORT__|${PORT}|g" -e "s|__NGINX_CONF__|${NGINX_CONF_DIR}|g" \
        deploy/nginx.conf.template > /tmp/nginx.conf
    chmod -R ugo+rw storage bootstrap/cache 2>/dev/null || true

    if nginx -e /dev/stderr -t -c /tmp/nginx.conf; then
        # -R: the container runs as root, as it did with `php -S`.
        php-fpm -R -y "$(pwd)/deploy/php-fpm.conf" &
        echo "=== php-fpm started (pid $!); nginx serving on port ${PORT} ==="
        exec nginx -e /dev/stderr -c /tmp/nginx.conf
    fi
    echo "=== nginx config test failed; falling back to php -S ===" >&2
fi

echo "=== Starting PHP built-in server on port ${PORT} (fallback) ==="
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT} -t public
