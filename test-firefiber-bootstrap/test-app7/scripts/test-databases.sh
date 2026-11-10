#!/usr/bin/env bash
# Boots disposable, unprivileged test database servers for the SahilPHP
# integration suites (Phase 7/8). Idempotent: re-running reports state.
#
#   MariaDB  → mysql://root@127.0.0.1:33306/sahil_tests
#   Postgres → pgsql://jinx@127.0.0.1:55432/sahil_tests
#
# The URLs are exported for the current shell:
#   eval "$(scripts/test-databases.sh export)"

set -euo pipefail
ROOT=/tmp/opencode

# ---------- MariaDB ----------
if ! command -v mariadbd >/dev/null; then
  echo "mariadbd not installed; skipping MySQL server" >&2
else
  if [ ! -d "$ROOT/db-mysql/mysql" ]; then
    mkdir -p "$ROOT/db-mysql"
    mariadb-install-db --datadir="$ROOT/db-mysql" --auth-root-authentication-method=normal >/dev/null 2>&1
  fi
  if ! mariadb -h 127.0.0.1 -P 33306 -u root -e "SELECT 1" >/dev/null 2>&1; then
    mariadbd --datadir="$ROOT/db-mysql" --socket="$ROOT/db-mysql/mysqld.sock" \
      --port=33306 --bind-address=127.0.0.1 \
      --pid-file="$ROOT/db-mysql/mysqld.pid" \
      >"$ROOT/db-mysql/server.log" 2>&1 &
    for _ in $(seq 1 30); do
      mariadb -h 127.0.0.1 -P 33306 -u root -e "SELECT 1" >/dev/null 2>&1 && break
      sleep 1
    done
  fi
  mariadb -h 127.0.0.1 -P 33306 -u root -e "CREATE DATABASE IF NOT EXISTS sahil_tests"
fi

# ---------- PostgreSQL ----------
if [ ! -x /usr/lib/postgresql/18/bin/pg_ctl ]; then
  echo "postgresql client binaries not installed; skipping PG server" >&2
else
  if [ ! -d "$ROOT/db-pg/PG_VERSION" ] && [ ! -f "$ROOT/db-pg/PG_VERSION" ]; then
    rm -rf "$ROOT/db-pg"; mkdir -p "$ROOT/db-pg"
    /usr/lib/postgresql/18/bin/initdb -D "$ROOT/db-pg" --auth-local=trust --auth-host=trust >/dev/null
  fi
  if ! /usr/lib/postgresql/18/bin/pg_isready -h 127.0.0.1 -p 55432 >/dev/null; then
    /usr/lib/postgresql/18/bin/pg_ctl -D "$ROOT/db-pg" \
      -o "-p 55432 -c unix_socket_directories=$ROOT/db-pg" \
      -l "$ROOT/db-pg/server.log" start >/dev/null
  fi
  psql -h 127.0.0.1 -p 55432 -U jinx -d postgres -c "SELECT 1" >/dev/null 2>&1 || \
    psql -h 127.0.0.1 -p 55432 -U postgres -d postgres -c "SELECT 1" >/dev/null 2>&1 || \
    true
  # The superuser is whoever ran initdb (jinx here).
  psql -h 127.0.0.1 -p 55432 -U jinx -d postgres -tc \
    "SELECT 1 FROM pg_database WHERE datname='sahil_tests'" | grep -q 1 || \
    psql -h 127.0.0.1 -p 55432 -U jinx -d postgres -c "CREATE DATABASE sahil_tests"
fi

echo "MySQL ready  : mysql://root@127.0.0.1:33306/sahil_tests"
echo "Postgres ready: pgsql://jinx@127.0.0.1:55432/sahil_tests"
