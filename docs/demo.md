# Skenario Demo

Panduan presentasi dari database kosong sampai pembayaran sandbox Midtrans. Semua pembayaran di sini **sandbox**: tidak ada uang sungguhan.

## 1. Persiapan (sekali)

1. Ikuti langkah **Menjalankan Lokal** di [README](../README.md).
2. Buat akun di [dashboard sandbox Midtrans](https://dashboard.sandbox.midtrans.com). Salin **Server Key** dan **Client Key** (Settings > Access Keys) ke `.env`:
   ```
   MIDTRANS_SERVER_KEY=SB-Mid-server-...
   MIDTRANS_CLIENT_KEY=SB-Mid-client-...
   ```
3. Buka tunnel agar webhook Midtrans sampai ke laptop:
   ```sh
   cloudflared tunnel --url http://localhost:8000   # atau: ngrok http 8000
   ```
   Isi **Payment Notification URL** di dashboard sandbox (Settings > Payment) dengan `https://<url-tunnel>/webhooks/midtrans`.
   Tanpa tunnel demo tetap jalan: status pesanan dicek ke API Midtrans setiap kali halaman pesanan dibuka.

## 2. Reset data sebelum presentasi

```sh
php artisan migrate:fresh --seed
composer run dev            # terminal 1
php artisan schedule:work   # terminal 2
```

Seeder menyiapkan:

| Data | Isi |
|---|---|
| Akun (password `password`) | `ceo@`, `operation@`, `po@` (PO Tech Conference), `user@example.com` |
| Event | Tech Conference (Regular Rp150.000, VIP Rp500.000), Music Festival (Festival Pass Rp250.000) |
| Penjualan contoh | 12 pesanan lunas, 1 kedaluwarsa, 1 tiket sudah check-in. `user@example.com` punya 2 e-ticket Tech Conference |

## 3. Alur demo

### A. Pembeli membeli tiket (user)

1. Buka `http://localhost:8000`, pilih **Tech Conference**, login sebagai `user@example.com`.
2. Pilih jumlah tiket, klik **Beli**. Kuota langsung direservasi 30 menit.
3. Klik **Bayar sekarang**, popup Snap muncul. Bayar dengan salah satu cara sandbox:
   - **Virtual Account / QRIS / e-wallet:** pilih metodenya, lalu "bayar" lewat [Payment Simulator](https://simulator.sandbox.midtrans.com) memakai nomor VA atau QR yang ditampilkan Snap.
   - **Kartu kredit:** `4811 1111 1111 1114`, CVV `123`, tanggal kedaluwarsa bebas di masa depan, OTP `112233`.

   Data uji bisa berubah, cek [dokumentasi testing Midtrans](https://docs.midtrans.com/docs/testing-payment-on-sandbox) kalau ditolak.
4. Webhook masuk, status menjadi **Lunas**, dan e-ticket QR muncul di halaman pesanan dan di **Tiket Saya**.

Poin yang bisa dijelaskan: status tidak pernah diambil dari browser. Status hanya berubah dari webhook yang signature-nya valid, atau dari cek status ke API Midtrans.

### B. Check-in (operation atau PO)

1. Login `/admin` sebagai `operation@example.com`, buka **Check-in**.
2. Masukkan kode tiket (`TKT-...`) dari halaman pesanan user, atau scan QR dengan scanner. Hasilnya **Check-in berhasil**.
3. Masukkan kode yang sama sekali lagi. Hasilnya **ditolak**, dengan keterangan waktu check-in sebelumnya.
4. Login sebagai `po@example.com`. Check-in tiket Music Festival **ditolak**, karena PO hanya berwenang untuk Tech Conference.

### C. Batasan akses per role

| Login sebagai | Yang terlihat |
|---|---|
| `ceo@` | Semua event, pesanan, user, **Audit Log**, dashboard semua event |
| `operation@` | Semua event dan pesanan, bisa refund. Tidak bisa kelola user atau melihat audit log |
| `po@` | Hanya Tech Conference (event, tiket, pesanan, dashboard). Tidak bisa refund |
| `user@` | Tidak bisa masuk `/admin` |

### D. Refund dan audit log (CEO)

1. Login `ceo@example.com`, buka **Pesanan**, pilih pesanan lunas **hasil pembayaran Midtrans di langkah A** (pesanan seeder tidak ada di Midtrans).
2. Klik **Refund**, isi alasan. Paling aman pakai pesanan yang dibayar dengan **GoPay**. Kartu kredit baru bisa di-refund setelah settlement, dan Virtual Account tidak bisa di-refund lewat API. Kalau gagal, panel menampilkan pesan error dan pesanan tetap lunas, yang juga bisa ditunjukkan sebagai penanganan error.
3. Ubah harga satu jenis tiket di **Events > Edit > Jenis Tiket**, dan ubah role satu user di **Users**.
4. Buka **Audit Log**: refund (alasan + nominal), perubahan harga (lama dan baru), dan perubahan role tercatat beserta pelakunya.

### E. Kuota dan pesanan kedaluwarsa

1. Buat pesanan baru tapi jangan dibayar.
2. Setelah 30 menit (ditambah tenggang 5 menit), `schedule:work` menjalankan `orders:expire`. Status menjadi **Kedaluwarsa** dan sisa kuota di halaman event kembali.
   Untuk mempercepat saat demo: `php artisan orders:expire --grace=-31` (mengabaikan masa reservasi, **khusus demo**).
