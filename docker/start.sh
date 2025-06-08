#!/bin/bash

# Start MySQL
service mysql start

# Wait for MySQL to be ready
while ! mysqladmin ping -h localhost --silent; do
    sleep 1
done

# Initialize MySQL if needed
if [ ! -d "/var/lib/mysql/app" ]; then
    mysql -e "CREATE DATABASE IF NOT EXISTS app;"
    mysql -e "CREATE USER IF NOT EXISTS 'app'@'localhost' IDENTIFIED BY 'password';"
    mysql -e "GRANT ALL PRIVILEGES ON app.* TO 'app'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
fi

# Start Apache in foreground
apache2-foreground 