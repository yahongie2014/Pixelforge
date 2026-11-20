#!/bin/sh
# ============================================================
# Docker entrypoint — runs before supervisord starts
# Handles: .env copy, key generate, DB migration, cache warm-up
# ============================================================
set -e

echo "▶ PromptEdit entrypoint starting..."

# Clean any stale bootstrap caches and ensure runtime directories exist
rm -f /var/www/html/bootstrap/cache/*.php
mkdir -p /var/log/supervisor /run/nginx

# 1. Copy .env.docker → .env if .env is missing (first boot)
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.docker ]; then
        cp /var/www/html/.env.docker /var/www/html/.env
        echo "  ✓ Copied .env.docker → .env"
    fi
fi

cd /var/www/html

# 2. Generate app key if not set
if grep -q '^APP_KEY=$' .env 2>/dev/null || ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
    echo "  ✓ App key generated"
fi

# 3. Wait for database to be ready (up to 60 s)
echo "  ⏳ Waiting for database..."
attempt=0
until php artisan db:monitor 2>/dev/null || [ $attempt -ge 30 ]; do
    attempt=$((attempt + 1))
    sleep 2
done
echo "  ✓ Database ready"

# 4. Run migrations
php artisan migrate --force --no-interaction
echo "  ✓ Migrations complete"

# 5. Seed if first run (detected by empty users table)
ROW_COUNT=$(php artisan tinker --no-interaction --execute="echo App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$ROW_COUNT" = "0" ]; then
    php artisan db:seed --force --no-interaction
    echo "  ✓ Database seeded"
fi

# 6. Optimise for production
php artisan package:discover --ansi
php artisan config:cache   --no-interaction
php artisan route:cache    --no-interaction
php artisan view:cache     --no-interaction
php artisan event:cache    --no-interaction
echo "  ✓ Laravel caches warm"

# 7. Fix permissions (in case volume was mounted fresh)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "▶ Entrypoint complete — handing off to supervisord"
exec "$@"
