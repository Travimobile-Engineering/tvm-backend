#!/bin/sh

# Start php artisan reverb:start in the background after 60s
(
  sleep 60
  php artisan queue:work &
  php artisan reverb:start
) &

# Start php-fpm in the foreground
exec php-fpm -F
