# Panduan Desain Sistem User

Project ini punya dua sistem:

| Sistem | Untuk | Teknologi | Desain |
|---|---|---|---|
| **User** | Pembeli: beranda, jelajah, detail event, login/register, checkout, Tiket Saya | Blade + Tailwind | **Wajib mengikuti referensi di folder ini** |
| **Internal** | CEO, Operation, PO di `/admin` | Filament | Tema bawaan Filament |

Screenshot referensi diambil dari platform ticketing yang sudah ada. Buat **persis** seperti referensi: layout, spasi, warna, tipografi, komponen, ikon, dan ilustrasi. Yang diganti hanya **nama brand** (dari `APP_NAME`, termasuk wordmark logo) dan teks kontak.

## Peta halaman

| Halaman | Referensi | Epic |
|---|---|---|
| Beranda | [01-beranda.png](reference/01-beranda.png) | #70 |
| Login & register | [02-login.png](reference/02-login.png) | #64 |
| Detail event | [03-detail-event.png](reference/03-detail-event.png) | #78 |
| Checkout 1: Pilih Kategori | [04-checkout-pilih-tiket.png](reference/04-checkout-pilih-tiket.png) | #82 |
| Checkout 2: Detail Pesanan | [05-checkout-detail-pesanan.png](reference/05-checkout-detail-pesanan.png) | #82 |
| Jelajah, Checkout 3–4, Tiket Saya | belum ada, ikuti gaya kartu & seksi di atas | #78, #82, #89 |

Fondasi (token, komponen, layout): epic #57.

## Warna (diambil dari piksel screenshot)

| Token | Hex | Dipakai di |
|---|---|---|
| `brand-950` | `#0d1e57` | Top bar (Our Journey, Biaya, ...) |
| `brand-900` | `#152c8c` | Navbar, footer, bar stepper checkout |
| `brand-700` | `#2641bf` | Panel kiri halaman login |
| `brand-600` | `#3c5fec` | Tombol utama (Pilih, Beli Sekarang, Lanjutkan, Pesan Sekarang) |
| `brand-100` | `#d9e0fb` | Latar badge kategori, pill (teksnya `brand-700` agar kontras AA) |
| `brand-50` | `#f3f8ff` | Latar kartu tiket, kartu form, kartu event |
| `ink` | `#09101e` | Judul & teks utama |
| `warning` | `#ffbb00` | Kartu **Batas Waktu Tersisa** |
| `danger` | `#dc2626` | Badge **Sold Out** (sedikit lebih gelap dari referensi agar kontras AA) |

Tombol nonaktif = `brand-600` dengan opasitas rendah (lihat tombol Lanjutkan di login).

## Tipografi & bentuk

- Font: **Plus Jakarta Sans** (perkiraan dari screenshot), berat 400/500/600/700.
- Teks kecil dan rapat: label 12–14px, judul seksi 16–18px semibold, judul event 20–24px.
- Sudut membulat: kartu ±12px, tombol/pill penuh (`rounded-full`) untuk menu dan badge, tombol aksi ±6–8px.
- Kartu tanpa border tebal: latar `brand-50` atau putih dengan shadow halus.

## Aturan

1. Sebelum mengerjakan task UI user, buka screenshot referensi yang ditautkan di issue.
2. Pakai token Tailwind (`tailwind.config.js`), bukan hex lepas di view.
3. Harga di sistem user pakai `rupiah($amount)` → `Rp250.510` (tanpa spasi, seperti referensi). Komponen dasar ada di `resources/views/components/ui/` (`x-ui.button`, `x-ui.input`, `x-ui.select`, `x-ui.badge`, `x-ui.panel`, `x-ui.section-title`, `x-ui.event-card`, `x-ui.qty-stepper`); pakai ulang, jangan salin markup.
4. Aksesibilitas tetap wajib: kontras AA, fokus terlihat, ikon selalu punya label teks.
5. Responsif sampai lebar ponsel (referensi hanya desktop; susun ulang kolom secara wajar).
6. Data pribadi di screenshot baru harus disensor sebelum di-commit (repo public).
