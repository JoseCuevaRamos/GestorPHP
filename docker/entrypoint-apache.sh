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

# If DB_SSL_CA is provided directly as an env var (PEM text), write it to a file.
# This avoids committing the CA into the repo while allowing the container to use it.
# Also support DB_SSL_CA_B64 (base64-encoded PEM) for platforms that don't preserve newlines.
if [ -n "${DB_SSL_CA_B64:-}" ]; then
  CA_PATH_B64="/etc/ssl/certs/tidb_ca_from_b64.pem"
  echo "[entrypoint] Decoding DB_SSL_CA_B64 into ${CA_PATH_B64}"
  mkdir -p "$(dirname "$CA_PATH_B64")"
  # decode base64; tolerate both single-line and wrapped input
  echo "$DB_SSL_CA_B64" | tr -d '\r' | base64 -d > "$CA_PATH_B64" 2>/dev/null || (echo "[entrypoint] ERROR: failed to decode DB_SSL_CA_B64" && false)
  chmod 644 "$CA_PATH_B64" || true
  export DB_SSL_CA="$CA_PATH_B64"
fi

if [ -n "${DB_SSL_CA:-}" ]; then
  # If DB_SSL_CA points to an existing file path inside the container, keep it.
  if [ -f "${DB_SSL_CA}" ]; then
    echo "[entrypoint] DB_SSL_CA is a file path and exists: ${DB_SSL_CA}"
  else
    # Otherwise assume DB_SSL_CA is the PEM contents; write to standard path
    CA_PATH="/etc/ssl/certs/tidb_ca.pem"
    echo "[entrypoint] Writing DB SSL CA contents from env to ${CA_PATH}"
    mkdir -p "$(dirname "$CA_PATH")"
    # Preserve newlines when writing the env var to file
    printf '%s' "$DB_SSL_CA" > "$CA_PATH"
    chmod 644 "$CA_PATH" || true
    export DB_SSL_CA="$CA_PATH"
  fi
fi

# Diagnostic logging: show what CA path will be used by Phinx/PDO and basic file info.
if [ -n "${DB_SSL_CA:-}" ]; then
  echo "[entrypoint] DEBUG: DB_SSL_CA=${DB_SSL_CA}"
  if [ -f "${DB_SSL_CA}" ]; then
    echo "[entrypoint] DEBUG: CA file exists at ${DB_SSL_CA} - listing details:";
    ls -l "${DB_SSL_CA}" || true
    echo "[entrypoint] DEBUG: Showing first 10 lines of CA (BEGIN/--- header expected):";
    # show first 10 lines so we can verify BEGIN/END without dumping whole cert
    head -n 10 "${DB_SSL_CA}" || true
    echo "[entrypoint] DEBUG: Showing last 2 lines of CA (END line expected):";
    tail -n 2 "${DB_SSL_CA}" || true
  else
    echo "[entrypoint] DEBUG: CA file does not exist at ${DB_SSL_CA}"
  fi
else
  echo "[entrypoint] DEBUG: DB_SSL_CA not set"
fi

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
