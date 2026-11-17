# Deploy ke Server

Panduan memasang aplikasi di server (VPS Linux) dengan Midtrans **sandbox**. Semua pembayaran tetap simulasi.

## 0. Yang perlu ada di server

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `intl`, `gd`, `zip`, `bcmath`
- Composer, Node 20+, Git
- Docker atau Podman (untuk MySQL dari `compose.yaml`)
- Web server (Nginx atau Caddy) + domain yang mengarah ke server, dan HTTPS

## 1. Ambil kode

```sh
git clone https://github.com/khalifaalhasan/laravel-filament-midtrans.git /var/www/eventmanagement
cd /var/www/eventmanagement
```

## 2. Siapkan `.env`

```sh
cp .env.production.example .env
nano .env   # isi semua nilai <...>: domain, password DB, password root DB, key sandbox Midtrans
```

`APP_KEY` boleh dibiarkan kosong; script deploy akan membuatnya.

## 3. Jalankan MySQL

`compose.yaml` di repo sudah siap pakai. Kalau Anda menaruhnya di folder lain, salin `compose.yaml` **dan** `.env` ke folder yang sama.

```sh
docker compose up -d      # atau: podman compose up -d
```

MySQL hanya terbuka di `127.0.0.1:3307`, jadi tidak bisa diakses dari internet.

## 4. Deploy

```sh
scripts/deploy.sh
```

Script ini menjalankan: `composer install --no-dev`, `npm ci && npm run build`, `key:generate` (kalau kosong), `migrate --force`, **seeder snapshot** (otomatis kalau database masih kosong), `storage:link`, dan cache konfigurasi/route/view/Filament.

| Perintah | Kapan |
|---|---|
| `scripts/deploy.sh` | Pemasangan pertama dan setiap update kode |
| `scripts/deploy.sh --seed` | Menerapkan ulang snapshot terbaru (aman diulang, data di-upsert) |
| `scripts/deploy.sh --demo` | Database kosong, tapi ingin data demo acak, bukan snapshot |

## 5. Proses latar (sekali saja)

**Cron** untuk scheduler (pesanan yang tidak dibayar menjadi kedaluwarsa dan kuotanya kembali):

```sh
crontab -e
* * * * * cd /var/www/eventmanagement && php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** dengan systemd, `/etc/systemd/system/eventmanagement-queue.service`:

```ini
[Unit]
Description=Event Management queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/eventmanagement
ExecStart=/usr/bin/php artisan queue:work --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```sh
sudo systemctl enable --now eventmanagement-queue
```

## 6. Web server

Document root ke `public/`. Contoh Caddy (HTTPS otomatis):

```
domain-anda.com {
    root * /var/www/eventmanagement/public
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
    encode gzip
}
```

Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis oleh user web server:

```sh
sudo chown -R www-data:www-data storage bootstrap/cache
```

## 7. Midtrans

Di [dashboard sandbox](https://dashboard.sandbox.midtrans.com) > Settings > Payment, isi **Payment Notification URL** dengan `https://domain-anda.com/webhooks/midtrans`. Dengan domain HTTPS publik, webhook bisa langsung sampai tanpa tunnel.

## Memperbarui data snapshot dari lokal

```sh
# di laptop
php artisan app:export-snapshot          # akun non-demo dianonimkan (aman untuk repo public)
git add database/seeders/snapshot && git commit -m "Update data snapshot" && git push

# di server
git pull && scripts/deploy.sh --seed
```

`app:export-snapshot --keep-personal` menyertakan akun dan data pembeli asli. **Hanya gunakan kalau repo private.**

Snapshot berisi user (password demo: `password`), role, event, jenis tiket, PO, pesanan, e-ticket, notifikasi pembayaran, audit log, serta file poster/banner/logo dari `storage/app/public`.
