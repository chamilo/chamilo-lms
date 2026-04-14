#!/bin/bash

# Start MySQL if not already running
mkdir -p /home/runner/mysql_run
mkdir -p /home/runner/mysql_data
if ! pgrep -x mysqld > /dev/null 2>&1; then
    echo "Starting MySQL..."
    rm -f /home/runner/mysql_run/mysql.sock /home/runner/mysql_run/mysql.sock.lock /home/runner/mysql_run/mysql.pid

    # Initialize data directory if the mysql system schema is missing.
    # Checking for the 'mysql' subdirectory is the reliable indicator of a
    # successful initialization — an empty dir check fails when a log file
    # was written there by a previous aborted init attempt.
    if [ ! -d /home/runner/mysql_data/mysql ]; then
        echo "Initializing MySQL data directory..."
        # Only wipe the directory when no real InnoDB data file is present.
        # ibdata1 is created during initialization and is the definitive
        # sign that a previous init completed. Without it the directory
        # holds at most stray log/tmp files from an aborted init attempt.
        if [ ! -f /home/runner/mysql_data/ibdata1 ]; then
            rm -rf /home/runner/mysql_data/*
        fi
        # Redirect to /tmp so no file is created inside mysql_data
        # before mysqld checks the directory is clean.
        mysqld --initialize-insecure \
               --datadir=/home/runner/mysql_data \
               --user=runner >> /tmp/mysql_init.log 2>&1
        echo "MySQL data directory initialized."
    fi

    mysqld --datadir=/home/runner/mysql_data \
           --socket=/home/runner/mysql_run/mysql.sock \
           --pid-file=/home/runner/mysql_run/mysql.pid \
           --port=3306 \
           --mysqlx=OFF \
           --user=runner \
           --bind-address=127.0.0.1 2>>/home/runner/mysql_data/mysql.log &

    # Wait for MySQL to be ready
    echo "Waiting for MySQL to start..."
    for i in $(seq 1 30); do
        if [ -S /home/runner/mysql_run/mysql.sock ] && mysql -u root --socket=/home/runner/mysql_run/mysql.sock -e "SELECT 1;" >/dev/null 2>&1; then
            echo "MySQL is ready!"
            break
        fi
        sleep 1
    done
fi

# Expose MySQL socket at the path PHP and system tools expect by default
mkdir -p /run/mysqld
ln -sf /home/runner/mysql_run/mysql.sock /run/mysqld/mysqld.sock

# Create DB and user if not exists
mysql -u root --socket=/home/runner/mysql_run/mysql.sock 2>/dev/null << 'SQLEOF'
CREATE DATABASE IF NOT EXISTS chamilo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'chamilo'@'localhost' IDENTIFIED BY 'chamilo_pass';
GRANT ALL PRIVILEGES ON chamilo.* TO 'chamilo'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

# Align MySQL timezone with PHP runtime (America/Sao_Paulo = UTC-3).
# Named timezone 'America/Sao_Paulo' requires populated mysql.time_zone_name tables,
# which are absent in this Nix environment (no /usr/share/zoneinfo).
# Brazil abolished DST in 2019, so America/Sao_Paulo is permanently UTC-3.
# Using the numeric offset '-03:00' avoids the dependency on timezone table population.
mysql -u root --socket=/home/runner/mysql_run/mysql.sock \
  -e "SET GLOBAL time_zone = '-03:00';" 2>/dev/null || true
echo "MySQL timezone alinhada: -03:00 (America/Sao_Paulo)"

# Generate JWT keys if not present
if [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    openssl genrsa -out config/jwt/private.pem 2048 2>/dev/null
    openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem 2>/dev/null
    echo "JWT keys generated"
fi

# Harden config/ permissions: make core Symfony config files read-only.
# config/jwt/ and config/jwt-test/ are intentionally left writable so
# JWT key generation above continues to work on fresh containers.
# settings_overrides.yaml and plugin.yaml are left writable as they can
# be updated at runtime by platform administrators.
chmod -R 0555 \
    config/packages \
    config/routes \
    config/routes.yaml \
    config/services.yaml \
    config/bundles.php \
    config/preload.php \
    2>/dev/null || true

# Clear Symfony cache
echo "Clearing Symfony cache..."
php -d memory_limit=512M bin/console cache:clear --env=dev --no-warmup 2>/dev/null || true

# Build frontend assets in background if not built
if [ ! -f public/build/entrypoints.json ]; then
    echo "Building frontend assets in background..."
    yarn build > /tmp/yarn_build.log 2>&1 &
    echo "Build started in background (PID: $!). Check /tmp/yarn_build.log for progress."
fi

# Start PHP built-in server on port 5000
# Pass the correct socket path so PDO/MySQLi resolve 'localhost' to our socket
echo "Starting PHP server on port 5000..."
exec php \
    -d pdo_mysql.default_socket=/home/runner/mysql_run/mysql.sock \
    -d mysqli.default_socket=/home/runner/mysql_run/mysql.sock \
    -d memory_limit=256M \
    -d upload_max_filesize=100M \
    -d post_max_size=100M \
    -d max_execution_time=300 \
    -d date.timezone=America/Sao_Paulo \
    -S 0.0.0.0:5000 -t public/
