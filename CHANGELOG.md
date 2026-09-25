# Changelog

Semua perubahan yang berdampak pada deploy dicatat di sini, mengikuti
[Semantic Versioning](https://semver.org/lang/id/).

## v1.0.2 — 2026-09-25

### Perbaikan

- **URL, skema, dan deteksi subdomain salah di balik reverse proxy.**
  Server produksi berdiri di belakang nginx; kini `Middleware::trustProxies`
  dipercayai (`X-Forwarded-Proto`/`X-Forwarded-Host`) sehingga `url()`,
  http/https, dan subdomain tenant dihitung dengan nilai header yang benar.
  Nginx wajib selalu menimpa `X-Forwarded-*`, dan aplikasi tidak boleh
  terekspos langsung tanpa nginx.

## v1.0.1 — 2026-09-25

### Perbaikan

- **Tombol "Lanjutkan dengan Google" tidak bisa diklik.** Tombol memakai
  `<Link>` Inertia yang menjalankan XHR, padahal `/auth/google/redirect`
  membalas 302 ke Google (bukan respons Inertia) — navigasi dibatalkan
  diam-diam (CORS). Kini dipakai `<a>` penuh di halaman Masuk, Daftar, dan
  terima undangan, sehingga browser berpindah halaman langsung ke Google.

## v1.0.0 — 2026-09-25

Rilis produksi pertama, untuk pemakaian pribadi. Aplikasi keuangan bersama
multi-tenant (keluarga, RT, komunitas kecil) di Indonesia.

### Fitur siap dipakai

**Multi-tenancy & akses**

- Tenant per subdomain (`{subdomain}.{domain}`) dengan isolasi data penuh
  (single DB + global scope; `stancl/tenancy` untuk domain identification).
- Pemilih tenant untuk user yang tergabung di lebih dari satu tenant, plus
  pembuatan tenant baru dari UI.

**Autentikasi**

- Mendaftar, masuk, keluar, lupa/reset kata sandi, dan verifikasi email
  (Laravel Fortify).
- Autentikasi dua faktor (2FA), passkey, dan login Google OAuth
  (tenant dibuat otomatis saat user baru masuk via Google).
- Login lintas origin (central ↔ subdomain) tetap berfungsi.

**Manajemen keuangan per tenant**

- **Kantong (akun)** — CRUD dengan logo, saldo awal terkunci, filter dan
  arsip; mask saldo Rupiah.
- **Kategori** — CRUD pengeluaran/pemasukan.
- **Transaksi** — CRUD dengan kategori, kantong, struk opsional, dan halaman
  detail.
- **Transfer** — antar kantong dengan CRUD dan halaman detail.
- **Kartu kredit & paylater** — detail tagihan dengan periode dan jatuh tempo
  otomatis, plus pelunasan.
- **Anggota** — undang lewat email/token, terima, ubah role, dan keluarkan
  anggota; batch role per matriks izin tenant.
- **Dashboard** — ringkasan saldo, arus kas berkala, dan komposisi kategori
  (chart.js).

**Paket & langganan**

- Dua paket: **Free** (Rp 0) dan **Pro** (Rp 35.000/bulan), dengan tabel
  perbandingan fitur di halaman langganan.
- Pemakaian dibatasi oleh paket: Free max 3 anggota & 3 kantong
  (`PlanFeatureChecker`); Pro menghilangkan batas.
- Subdomain kustom hanya untuk Pro (validasi `alpha_dash`, unik, reserved
  words).
- Penurunan ke Free mengembalikan subdomain otomatis yang acak.

**Admin platform (central `/admin`)**

- Statistik lintas tenant, daftar pengguna, dan daftar tenant.
- Set paket tenant (Free/Pro) oleh super-admin; super-admin dibuat lewat
  seeder atau `php artisan user:promote`.

**Lainnya**

- Landing page dan halaman masuk yang selaras bahasa desain Fluxa.
- Pengaturan profil, tampilan, keamanan, dan tenant (termasuk zona berbahaya).

### Belum siap / simulasi

- **Pembayaran (Xendit)** — tombol naik/turun paket di halaman langganan
  _langsung berlaku tanpa pembayaran nyata_: gateway masih `log`
  (`.env` `BILLING_GATEWAY=log`), webhook belum diverifikasi, dan belum ada
  checkout sungguhan. Jangan set `BILLING_GATEWAY=xendit` di produksi sampai
  alur pembayaran selesai.
- **Export laporan (PDF/Excel)** — tercantum sebagai fitur Pro di tabel
  perbandingan, belum diimplementasikan.
