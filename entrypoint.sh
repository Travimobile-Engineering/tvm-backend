#!/bin/bash

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -F &

# Verify PHP-FPM is running
sleep 2
if ! pgrep "php-fpm" >/dev/null; then
    echo "ERROR: PHP-FPM failed to start!" >&2
    exit 1
fi

# Wait for application initialization
echo "Waiting for application warmup..."
sleep 60

# Execute the CMD passed from Dockerfile (or docker run)
echo "Starting main process..."
exec "$@"