# Opsi Keputusan Teknologi

Kriteria utama: **pakai modul komunitas yang sudah matang** untuk auth, RBAC, admin panel, dan payment gateway, supaya kita fokus ke logika bisnis event.

## A. Opsi Stack Sistem

### Opsi 1: Laravel 12 + Filament (rekomendasi)

| Kebutuhan | Modul siap pakai |
|---|---|
| Auth | Laravel Breeze / Jetstream / Fortify |
| Role & permission (termasuk scoped per event) | `spatie/laravel-permission` (fitur *teams* = scope per event) |
| Admin panel (CEO, Operation, PO) | Filament v3/v4 + `bezhansalleh/filament-shield` |
| Halaman publik & checkout user | Blade + Livewire, atau Inertia + Vue/React |
| Queue (webhook, email tiket) | Laravel Queue + Horizon |
| Audit log | `spatie/laravel-activitylog` |
| QR e-ticket | `simplesoftwareio/simple-qrcode` |
| Payment | SDK resmi Midtrans/Xendit (PHP), banyak wrapper Laravel komunitas |

- **Kelebihan:** paling "tinggal pakai". Panel admin multi-role jadi dalam hitungan hari. Ekosistem payment gateway Indonesia paling banyak contoh dan tutorialnya di PHP/Laravel. Hosting murah (VPS/shared).
- **Kekurangan:** frontend publik kurang fleksibel dibanding React murni (bisa diatasi dengan Inertia).

### Opsi 2: Next.js (App Router) full-stack

| Kebutuhan | Modul siap pakai |
|---|---|
| Auth | Better Auth (punya plugin organization/role) atau Auth.js |
| ORM | Prisma atau Drizzle + PostgreSQL |
| RBAC | Better Auth access control / CASL |
| Admin panel | shadcn/ui + TanStack Table (dibangun sendiri), atau Refine |
| Background job | Inngest / Trigger.dev / BullMQ |
| Payment | `midtrans-client` (npm), `xendit-node` |

- **Kelebihan:** UX frontend paling bagus, satu bahasa (TypeScript), cocok deploy ke Vercel.
- **Kekurangan:** tidak ada padanan Filament yang setara; dashboard admin multi-role harus dirakit sendiri, jadi butuh waktu lebih lama. Webhook dan job di serverless perlu perhatian ekstra.

### Opsi 3: Hybrid: Laravel (API + Filament admin) + Next.js (frontend publik)

- **Kelebihan:** admin cepat jadi dengan Filament, halaman publik/checkout tetap modern dan SEO-friendly.
- **Kekurangan:** dua codebase, dua deploy, perlu atur auth lintas aplikasi (Sanctum). Cocok jika tim sudah punya orang frontend dan backend terpisah.

### Ringkasan

| | Laravel + Filament | Next.js | Hybrid |
|---|---|---|---|
| Kecepatan ke MVP | ⭐⭐⭐ | ⭐⭐ | ⭐⭐ |
| Modul RBAC + admin siap pakai | ⭐⭐⭐ | ⭐ | ⭐⭐⭐ |
| Fleksibilitas UI publik | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| Kompleksitas operasional | Rendah | Sedang | Tinggi |

## B. Opsi Payment Gateway (Indonesia)

| Gateway | Metode | SDK resmi | Catatan |
|---|---|---|---|
| **Midtrans** | VA semua bank, QRIS, GoPay, ShopeePay, kartu, cicilan | PHP, Node, dll. | Paling populer, **Snap** (popup checkout) sangat cepat diintegrasikan. Dokumentasi & contoh komunitas paling banyak. |
| **Xendit** | VA, QRIS, e-wallet, kartu, retail outlet | PHP, Node, dll. | API modern, fitur **Invoice/Payment Link** cocok untuk tiket, ada **disbursement** (berguna untuk refund/payout). Juga mendukung beberapa negara SEA. |
| **DOKU** | VA, QRIS, e-wallet, kartu, paylater | PHP, Node, Java | Pemain lama, Checkout page siap pakai, kuat di enterprise. |
| **Duitku / Tripay** | VA, QRIS, e-wallet, retail | Wrapper komunitas | Fee lebih murah, cocok volume kecil–menengah, onboarding mudah. SDK/dukungan enterprise lebih terbatas. |

**Rekomendasi:** mulai dengan **Midtrans Snap** atau **Xendit Invoice** (keduanya punya hosted checkout, jadi kita tidak menyimpan data kartu dan beban PCI kecil). Bangun di balik interface `PaymentGateway` supaya provider kedua bisa ditambah nanti.

Hal yang perlu dicek sebelum memilih: fee per metode (terutama QRIS & VA), lama settlement, syarat dokumen badan usaha, dan dukungan refund via API.

## C. Pendukung

- **Database:** PostgreSQL (atau MySQL 8 jika pilih Laravel dan sudah familiar).
- **Storage:** S3-compatible (Cloudflare R2 / MinIO) untuk poster event & lampiran.
- **Email:** Resend / Mailgun / SES untuk e-ticket.

## Keputusan

**Diputuskan 2026-11-16:**

- **Stack:** Laravel 12 + Filament (admin panel), `spatie/laravel-permission`, `spatie/laravel-activitylog`.
- **Payment gateway:** Midtrans Snap, **sandbox saja** (project tugas, tidak sampai production).
- **Alasan:** paling cepat ke MVP, akun sandbox Midtrans tanpa verifikasi dokumen, popup Snap + Payment Simulator memudahkan demo.
- **Webhook lokal:** pakai tunnel (`ngrok`/`cloudflared`), dengan fallback cek status ke API Midtrans setelah redirect.
