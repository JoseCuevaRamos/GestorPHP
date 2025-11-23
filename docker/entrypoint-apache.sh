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

# Ensure logs directory exists and is writable by www-data
mkdir -p /var/www/html/logs
chown -R www-data:www-data /var/www/html/logs || true

# Run migrations if phinx is available. Try a few times (DB may still be warming up).
if [ -x "vendor/bin/phinx" ]; then
  echo "[entrypoint] phinx found, attempting migrations (env: ${PHINX_ENV:-production})"
  MAX_ATTEMPTS=5
  attempt=1
  while [ $attempt -le $MAX_ATTEMPTS ]; do
    echo "[entrypoint] phinx attempt #${attempt}..."
    if vendor/bin/phinx migrate -e ${PHINX_ENV:-production}; then
      echo "[entrypoint] phinx migrate succeeded"
      break
    else
      echo "[entrypoint] phinx migrate failed on attempt ${attempt}"
      attempt=$((attempt+1))
      sleep 3
    fi
  done
  if [ $attempt -gt $MAX_ATTEMPTS ]; then
    echo "[entrypoint] phinx migrate failed after ${MAX_ATTEMPTS} attempts, aborting start"
    exit 1
  fi
else
  echo "[entrypoint] phinx not found, skipping migrations"
fi

echo "[entrypoint] Starting Apache..."
exec "$@"
