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
#   4. Start the PHP server.
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

if has_table tenants; then
    echo "=== DB healthy (tenants table present) ==="
elif has_table migrations; then
    echo "=== DB in partial state — wiping public schema for clean migration run ==="
    psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -c \
        "DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres; GRANT ALL ON SCHEMA public TO public;"
fi

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Starting server on port ${PORT:-8080} ==="
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT:-8080} -t public
