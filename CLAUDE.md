# CLAUDE.md

Panduan untuk agent AI yang bekerja di repo ini.

## Konteks

Sistem manajemen event multi-event, single vendor, terintegrasi payment gateway. Baca [README.md](README.md) untuk tujuan dan role. Opsi teknologi ada di [docs/tech-options.md](docs/tech-options.md). **Stack:** Laravel 13 + Filament, payment gateway Midtrans Snap (sandbox saja). Backlog di GitHub Issues: kerjakan per issue `task`, satu issue satu PR.

## Aturan Domain

- **Role:** `ceo` (superadmin), `operation`, `project_officer` (scoped per event), `user`. Cek otorisasi PO selalu terhadap event spesifik, jangan hanya cek nama role.
- **Uang:** simpan nominal sebagai integer (rupiah, tanpa desimal). Jangan pakai float.
- **Status pembayaran hanya berubah dari webhook gateway** yang sudah diverifikasi signature-nya, atau dari pengecekan status ke API gateway. Jangan pernah percaya redirect/callback di sisi browser.
- **Webhook harus idempotent:** simpan event ID/transaction ID dari gateway, abaikan duplikat.
- **Kuota tiket:** kurangi kuota dalam transaksi DB dengan lock (atau reservasi dengan expiry) untuk mencegah overselling.
- **Payment gateway di balik interface/adapter** (`PaymentGateway`: `createCharge`, `verifyWebhook`, `getStatus`, `refund`) agar bisa ganti provider.
- Aksi sensitif (refund, ubah harga, ubah role, ubah kredensial gateway) wajib dicatat di audit log.

## Keamanan

- Kredensial gateway (server key, secret) hanya di environment variable, tidak pernah di-commit atau dikirim ke client.
- Gunakan mode sandbox untuk development dan test.

## Bahasa

Dokumentasi dan komunikasi dengan user: Bahasa Indonesia. Kode, nama variabel, dan commit message: Bahasa Inggris.
