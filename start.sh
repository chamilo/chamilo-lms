#!/bin/bash

# Start MySQL if not already running
mkdir -p /home/runner/mysql_run
mkdir -p /home/runner/mysql_data
if ! pgrep -x mysqld > /dev/null 2>&1; then
    echo "Starting MySQL..."
    rm -f /home/runner/mysql_run/mysql.sock /home/runner/mysql_run/mysql.sock.lock /home/runner/mysql_run/mysql.pid

    # Initialize data directory on first run (empty directory = never initialized)
    if [ -z "$(ls -A /home/runner/mysql_data 2>/dev/null)" ]; then
        echo "Initializing MySQL data directory..."
        mysqld --initialize-insecure \
               --datadir=/home/runner/mysql_data \
               --user=runner 2>>/home/runner/mysql_data/mysql.log
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

# Create DB and user if not exists
mysql -u root --socket=/home/runner/mysql_run/mysql.sock 2>/dev/null << 'SQLEOF'
CREATE DATABASE IF NOT EXISTS chamilo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'chamilo'@'localhost' IDENTIFIED BY 'chamilo_pass';
GRANT ALL PRIVILEGES ON chamilo.* TO 'chamilo'@'localhost';
FLUSH PRIVILEGES;
SQLEOF

# Generate JWT keys if not present
if [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    openssl genrsa -out config/jwt/private.pem 2048 2>/dev/null
    openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem 2>/dev/null
    echo "JWT keys generated"
fi

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
echo "Starting PHP server on port 5000..."
exec php -S 0.0.0.0:5000 -t public/
