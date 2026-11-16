<img src="assets/icon.svg" width="64" height="64" alt="">

# Event Management + Payment Gateway

Sistem manajemen event **multi-event, single vendor**: satu penyelenggara (perusahaan kita) mengelola banyak event sekaligus, lengkap dengan penjualan tiket dan pembayaran online lewat payment gateway.

## Tujuan Project

1. **Satu platform untuk semua event perusahaan.** Setiap event punya data, tiket, peserta, dan laporannya sendiri, tapi tetap di bawah satu vendor.
2. **Pembayaran online otomatis.** Peserta bisa membeli tiket dan membayar (VA, QRIS, e-wallet, kartu) tanpa konfirmasi manual. Status pesanan diperbarui otomatis lewat webhook.
3. **Pembagian akses yang jelas per peran.** Tiap orang hanya melihat dan mengubah apa yang menjadi tanggung jawabnya.
4. **Laporan yang bisa dipercaya.** Pendapatan, penjualan tiket, dan kehadiran per event bisa dilihat real-time oleh PO dan CEO.

## Peran (Role)

| Role | Cakupan | Kemampuan utama |
|---|---|---|
| **CEO (Superadmin)** | Seluruh sistem | Kelola semua event, user, role, pengaturan payment gateway, lihat semua laporan keuangan |
| **Operation** | Lintas event | Operasional harian: verifikasi peserta, check-in, bantu refund, kelola konten |
| **Project Officer (PO)** | **Per event** (ditugaskan) | Kelola event yang menjadi tanggung jawabnya: jenis tiket, kuota, harga, jadwal, laporan event tersebut |
| **User (authenticated)** | Data miliknya sendiri | Daftar/login, beli tiket, bayar, lihat e-ticket & riwayat transaksi |

> Catatan: PO bersifat *scoped* — satu orang bisa jadi PO di event A tapi bukan di event B. Ini butuh relasi `event_user_roles`, bukan sekadar role global.

## Fitur Inti (MVP)

- Manajemen event (CRUD, status draft/published/closed)
- Jenis tiket per event (harga, kuota, periode penjualan)
- Checkout & pembayaran via payment gateway
- Webhook handler (idempotent) untuk update status pembayaran
- E-ticket dengan QR code + check-in
- Dashboard & laporan per event dan agregat
- Audit log untuk aksi sensitif (refund, ubah harga, ubah role)

## Menjalankan Lokal

Butuh PHP 8.3+, Composer, dan Node 20+. Database default-nya SQLite.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link   # poster event
npm install && npm run build
composer run dev   # server, queue, dan vite sekaligus
```

Isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` di `.env` dengan key **sandbox** dari [dashboard sandbox Midtrans](https://dashboard.sandbox.midtrans.com) (Settings > Access Keys).

Panel admin ada di `/admin`. Akun demo (password semua `password`): `ceo@example.com`, `operation@example.com`, `po@example.com` (PO event Tech Conference), `user@example.com`.

## Status

Tahap perencanaan. Stack: **Laravel 13 + Filament + Midtrans Snap (sandbox)**, lihat [docs/tech-options.md](docs/tech-options.md). Backlog ada di GitHub Issues (label `epic` dan `task`).
