#!/usr/bin/env bash
# Install / update the app on a server in production mode.
#
#   scripts/deploy.sh            install deps, build, migrate; seed only if the database is empty
#   scripts/deploy.sh --seed     also (re)apply database/seeders/snapshot (safe to repeat)
#   scripts/deploy.sh --demo     seed generated demo data instead of the snapshot (empty database only)
#
# Needs: PHP 8.3+ (pdo_mysql, intl, gd, zip), Composer, Node 20+, and MySQL from compose.yaml running.
set -euo pipefail
cd "$(dirname "$0")/.."

SEED=auto
for arg in "$@"; do
    case "$arg" in
        --seed) SEED=snapshot ;;
        --demo) SEED=demo ;;
        *) echo "Argumen tidak dikenal: $arg" >&2; exit 1 ;;
    esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

if [ ! -f .env ]; then
    cp .env.production.example .env
    echo "File .env dibuat dari .env.production.example. Isi nilai <...> (domain, password DB, key Midtrans), lalu jalankan ulang script ini." >&2
    exit 1
fi
if grep -q '<' .env; then
    echo "Masih ada nilai <...> yang belum diisi di .env:" >&2
    grep -n '<' .env >&2
    exit 1
fi

step "Maintenance mode"
php artisan down --retry=15 2>/dev/null || true
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

step "Dependensi PHP (tanpa paket development)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

step "Build aset frontend"
npm ci --no-audit --no-fund
npm run build
rm -f public/hot   # a leftover dev-server file would make pages load assets from Vite instead of public/build

if ! grep -q '^APP_KEY=base64:' .env; then
    step "Membuat APP_KEY"
    php artisan key:generate --force
fi

step "Migrasi database"
php artisan migrate --force

USERS=$(php artisan tinker --execute 'echo \App\Models\User::count();' 2>/dev/null | tail -n1 | tr -dc '0-9')
if [ "$SEED" = auto ] && [ "${USERS:-0}" = 0 ]; then
    SEED=snapshot
fi
case "$SEED" in
    snapshot)
        step "Seeder snapshot (data dari lokal)"
        php artisan db:seed --class=SnapshotSeeder --force ;;
    demo)
        step "Seeder data demo"
        if [ "${USERS:-0}" != 0 ]; then echo "Database sudah berisi user; --demo hanya untuk database kosong." >&2; exit 1; fi
        php artisan db:seed --force ;;
    *)
        echo "Database sudah berisi data, seeder dilewati (pakai --seed untuk menerapkan snapshot lagi)." ;;
esac

step "Storage link & cache"
php artisan storage:link 2>/dev/null || true
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
php artisan queue:restart

step "Selesai"
cat <<'MSG'
Pastikan juga sudah ada (sekali saja, lihat docs/deploy.md):
  - cron:   * * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1
  - worker: php artisan queue:work --tries=3   (systemd / supervisor)
  - web server (Nginx/Caddy) dengan document root ke public/ dan HTTPS
  - Midtrans sandbox > Payment Notification URL: https://<domain>/webhooks/midtrans
MSG
