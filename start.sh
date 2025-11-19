#!/bin/sh
set -e

echo "🛠 Running migrations..."
if ! php artisan migrate --force; then
  echo "❌ Migration failed. Exiting..."
  exit 1
fi

echo "⚙️  Caching config..."
if ! php artisan cache:clear; then
  echo "❌ Config cache failed. Exiting..."
  exit 1
fi

# Optional: You can skip sleep in Kubernetes since pod startup time isn't tight
echo "🚀 Starting queue worker and reverb..."
php artisan queue:work &    # background
php artisan reverb:start &  # background
# php artisan schedule:work &  # background

# Start php-fpm in the foreground so the container doesn't exit
echo "📦 Starting php-fpm..."
exec php-fpm -F



