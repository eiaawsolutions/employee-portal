#!/usr/bin/env bash
# EIAAW Workforce — Railway boot script.
#
# Boot sequence (idempotent on healthy DB; self-heals from a partial migration):
#   1. Inspect the DB: does it have a `tenants` table?
#      a. If yes → DB is healthy, skip schema load
#      b. If no, BUT a `migrations` table exists → previous boot failed
#         partway. Drop + recreate the public schema so we can start clean.
#      c. If neither → fresh DB, no cleanup needed.
#   2. If we reach here without a `tenants` table, load the pgsql baseline
#      schema dump via `psql -v ON_ERROR_STOP=1`.
#   3. Run `php artisan migrate --force` for any post-baseline additions.
#   4. Start the PHP server.

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
    echo "=== DB has tenants table — schema is loaded ==="
elif has_table migrations; then
    echo "=== DB is in a partial state (migrations table without tenants) — wiping public schema ==="
    psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -c \
        "DROP SCHEMA public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO postgres; GRANT ALL ON SCHEMA public TO public;"
fi

if ! has_table tenants; then
    echo "=== Loading pgsql baseline schema ==="
    psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f database/schema/pgsql-schema.sql
fi

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Starting server on port ${PORT:-8080} ==="
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT:-8080} -t public
