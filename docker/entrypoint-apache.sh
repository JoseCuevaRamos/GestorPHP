#!/usr/bin/env bash
set -e

# Simple entrypoint for Render: wait DB, run migrations if available, then start Apache

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
RETRIES=12
SLEEP_SECONDS=3

echo "[entrypoint] Waiting for database ${DB_HOST}:${DB_PORT} (up to $((RETRIES*SLEEP_SECONDS))s) ..."
i=0
until (echo > /dev/tcp/${DB_HOST}/${DB_PORT}) >/dev/null 2>&1; do
  i=$((i+1))
  if [ "$i" -ge "$RETRIES" ]; then
    echo "[entrypoint] Timeout waiting for ${DB_HOST}:${DB_PORT}, continuing anyway"
    break
  fi
  sleep ${SLEEP_SECONDS}
done

# Run migrations if phinx is available
if [ -x "vendor/bin/phinx" ]; then
  echo "[entrypoint] Running phinx migrations..."
  vendor/bin/phinx migrate -e ${PHINX_ENV:-production} || echo "[entrypoint] phinx migrate finished with non-zero exit"
else
  echo "[entrypoint] phinx not found, skipping migrations"
fi

echo "[entrypoint] Starting Apache..."
exec "$@"
