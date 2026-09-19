# PRD - SaaS Manajemen Keuangan Multi-Tenant

2026-09-19 · @Someone

## Overview & Tujuan Produk

SaaS manajemen keuangan multi-tenant, terinspirasi konsep "kantong" ala Bank Jago, ditujukan untuk pengelolaan uang bersama (keluarga, komunitas, RT, kelompok kecil) — bukan sekadar pencatatan personal.

Diferensiasi utama dibanding aplikasi keuangan personal (Finansialku, Money Lover, dll): kolaborasi multi-user dalam satu tenant, dan custom subdomain sebagai identitas tenant.

Stack: Laravel + Inertia.js + Vue 3 (stack yang sudah dikuasai tim).

## Ruang Lingkup Fitur Phase 1

1. Buat akun ("kantong") — mirip fitur kantong Bank Jago
2. Transfer antar akun dalam satu tenant
3. Transaksi pengeluaran, tercatat dengan sumber (akun) dan kategori
4. Transaksi pemasukan, masuk ke akun terkait
5. Kategori pemasukan dan pengeluaran (default + custom per tenant)
6. Tenant multi-user — undang anggota lain untuk kelola uang bersama
7. Login/register via Google OAuth
8. Layanan subscription (free vs paid plan)
9. Tampilan responsif, user-friendly di HP (mobile-first)

## Keputusan Arsitektur Multi-Tenancy

**Keputusan: single database, shared schema, dengan kolom `tenant_id` di setiap tabel** (bukan database-per-tenant).

Alasan:

- Tenant (keluarga/kelompok kecil) datanya relatif kecil per tenant — single DB jauh lebih efisien untuk banyak tenant kecil.
- Migration schema jauh lebih sederhana — cukup jalan sekali, tidak perlu diulang ke ratusan/ribuan database tenant.
- Biaya hosting lebih rendah di tahap awal (bootstrap).
- Reporting/analytics lintas tenant (untuk kebutuhan admin/billing) jadi query biasa, bukan federated query.

Mitigasi risiko kebocoran data antar tenant (karena ini data finansial):

- Global scope Eloquent otomatis inject `tenant_id` di setiap query (lihat trait `BelongsToTenant`).
- Laravel Policy sebagai lapisan kedua otorisasi, tidak mengandalkan scope saja.

Multi-database dipertimbangkan hanya sebagai fase lanjutan, untuk tenant enterprise yang butuh isolasi data penuh, atau tenant dengan volume data sangat besar. Package `stancl/tenancy` dipilih karena mendukung kedua mode, sehingga migrasi ke multi-db di masa depan tidak perlu rewrite total.

## Skema Database

| Tabel | Kolom Kunci | Catatan |
| --- | --- | --- |
| `users` | email, google\_id, avatar | Password nullable — login via Google |
| `tenants` | name, subdomain, owner\_id | subdomain unique |
| `domains` | domain, tenant\_id | Dari package stancl/tenancy; 1 tenant bisa >1 domain untuk alias/redirect |
| `tenant_user` (pivot) | tenant\_id, user\_id, role | role: owner/admin/member; unique(tenant\_id, user\_id) |
| `tenant_invitations` | tenant\_id, email, token, status, expires\_at | status: pending/accepted/expired/revoked; unique(tenant\_id, email, status='pending') |
| `accounts` ("kantong") | tenant\_id, name, type, balance | balance disimpan (cached), di-update via DB transaction |
| `categories` | tenant\_id, name, type, is\_default | type: income/expense; per-tenant row, bukan shared global |
| `transactions` | tenant\_id, account\_id, category\_id, type, amount | Soft delete; balance reversed via observer saat dihapus |
| `transfers` | tenant\_id, from\_account\_id, to\_account\_id, amount | Dipisah dari transactions agar laporan cash flow bersih |
| `plans` | name, price, billing\_period, features (json) | Tabel terpisah, bukan enum — harga/fitur bisa diubah tanpa deploy |
| `subscriptions` | tenant\_id, plan\_id, status, xendit\_subscription\_id, current\_period\_end | status: active/past\_due/cancelled/expired |

Keputusan desain penting:

- **Transfer dipisah dari transactions** — transfer bukan income/expense riil, mencampurnya mengotori laporan cash flow.
- **`balance` cached di tabel `accounts`**, bukan dihitung ulang tiap request — wajib pakai `DB::transaction()` agar balance dan histori transaksi tidak pernah out-of-sync.
- **Soft delete untuk `transactions` dan `transfers`** — audit trail data keuangan tidak boleh hilang permanen.
- **Kategori default di-seed per tenant baru** (Gaji, Makan, Transport, dll), tapi tetap row per-tenant agar tenant bebas edit/hapus tanpa memengaruhi tenant lain.

## Flow Invite Anggota Tenant

1. Owner/admin input email + role (admin/member) di halaman "Invite Anggota".
2. Sistem cek email belum jadi member tenant tersebut.
3. Buat row `tenant_invitations` dengan token random, kirim email berisi link `https://{subdomain}.appkamu.com/invitations/{token}`.
4. Recipient klik link → percabangan logic:

**Email sudah punya akun user:**

- Belum login → redirect ke Google OAuth, token disimpan di session untuk redirect balik.
- Sudah login tapi email beda dari yang diundang → tampilkan error, jangan auto-accept (mencegah pencurian invite).
- Email cocok → insert ke `tenant_user`, update invitation jadi `accepted`.

**Email belum pernah register:**

- Diarahkan ke Google OAuth.
- Email Google cocok dengan invitation → buat user baru + langsung insert ke `tenant_user`.
- Email Google tidak cocok → tampilkan pesan untuk login dengan akun yang benar.

Prinsip keamanan kunci: validasi email selalu terhadap `invitation->email`, bukan asumsi siapa yang klik link.

**Edge cases:**

- `expires_at`: 7 hari dari `created_at`; kalau lewat, owner bisa resend (token baru, invalidate lama).
- Invite ulang ke email yang sama sebelum accept → update row yang ada, bukan bikin duplikat.
- Owner bisa revoke invitation sebelum diklik → status `revoked`.

Route invite (`/invitations/{token}`) berada di central domain, di luar middleware tenant context, karena user yang klik invite belum tentu "berada" di tenant manapun secara sesi.

## Subdomain Resolution & Generator

**Struktur domain:** central domain (`appkamu.com`) untuk landing, login, billing, admin; tenant domain (`{subdomain}.appkamu.com`) untuk seluruh route aplikasi utama dengan tenant context aktif — pakai `stancl/tenancy` khusus fitur domain identification-nya saja (bukan fitur database-per-tenant, karena kita pakai single DB + scoping).

**Generator subdomain (tenant belum subscribe):** kombinasi kata acak yang ramah dibaca, pakai package `atrox/haikunator` (`composer require atrox/haikunator`) — hasil contoh: `wispy-dust-42`, `purple-breeze-17`. Wordlist bisa dioverride (`Haikunator::$ADJECTIVES`, `$NOUNS`) kapan saja jika ingin nuansa lokal, tanpa harus menulis manual dari awal.

**Validasi reserved words:** `www`, `api`, `admin`, `app`, `mail`, `billing` diblok dari generator maupun input custom subdomain.

**Custom subdomain (fitur subscriber):** validasi `alpha_dash`, min 3 - max 30 karakter, unique di tabel `domains`, tidak termasuk reserved words.

**Transisi ke custom subdomain saat subscribe:** subdomain lama TIDAK langsung dihapus — tambah row domain baru, tandai domain lama dengan `redirects_to` agar redirect 301, mencegah broken link kalau ada yang sudah share/bookmark subdomain lama.

**Infrastruktur:** wildcard DNS (`*.appkamu.com`) + wildcard SSL via Let's Encrypt DNS-01 challenge, atau lebih simpel pakai Cloudflare + Origin Certificate (cocok dengan server Oracle Cloud/Biznet Gio yang sudah dipakai di project lain).

## Role & Permission Matrix

| Aksi | Owner | Admin | Member |
| --- | --- | --- | --- |
| Lihat semua data tenant | Ya | Ya | Ya |
| Buat account/kantong baru | Ya | Ya | Ya |
| Edit/hapus account | Ya | Ya | Tidak |
| Buat transaksi | Ya | Ya | Ya |
| Edit/hapus transaksi | Ya | Ya | Hanya milik sendiri |
| Buat/edit/hapus kategori | Ya | Ya | Tidak |
| Invite anggota baru | Ya | Ya | Tidak |
| Ubah role anggota | Ya | Tidak | Tidak |
| Kick/remove anggota | Ya | Ya (kecuali owner) | Tidak |
| Ubah pengaturan tenant | Ya | Tidak | Tidak |
| Lihat billing/subscription | Ya | Tidak | Tidak |
| Hapus tenant | Ya | Tidak | Tidak |

Implementasi via Laravel Policy per model (`AccountPolicy`, `TransactionPolicy`, `CategoryPolicy`, `TenantUserPolicy`), dicek lewat helper `Tenant::hasRole($user, $roles)` yang query tabel `tenant_user`.

Di frontend (Inertia + Vue), permission dikirim sebagai prop per-item (`can_edit`, `can_delete`) supaya tombol aksi tidak muncul di UI kalau user tidak berhak — backend Policy tetap jadi source of truth keamanan.

**Guard penting:** owner tidak bisa keluar dari tenant miliknya sendiri tanpa transfer ownership dulu — minimal harus selalu ada 1 owner aktif per tenant.

## Flow Subscription & Billing

**Payment provider: Xendit** (dipilih dibanding Midtrans karena fitur Recurring Payment native-nya lebih matang untuk model subscription berulang; Midtrans lebih native untuk transaksi sekali bayar).

**Struktur plan:** tabel `plans` terpisah (bukan enum) berisi `price`, `billing_period`, `features` (json: `custom_subdomain`, `max_members`, `max_accounts`, dst) — supaya harga/fitur bisa diubah tanpa deploy kode.

**Flow checkout:**

1. Owner tenant klik upgrade → backend create Xendit Customer + Recurring Plan.
2. User diarahkan ke checkout Xendit (VA bank/e-wallet/QRIS).
3. Setelah bayar, Xendit kirim webhook — **webhook adalah source of truth**, bukan redirect URL setelah bayar (user bisa saja menutup tab sebelum redirect selesai).
4. Webhook handler verifikasi token dari header (`x-callback-token`), lalu update `subscriptions` sesuai event (`recurring.plan.activated`, `recurring.cycle.succeeded`, `recurring.cycle.failed`, `recurring.plan.stopped`).

**Downgrade/cancel:** beri grace period 7-14 hari setelah `current_period_end` sebelum subdomain custom dicabut otomatis (mengurangi risiko user kaget karena kartu ditolak sesaat).

**Feature gating:** `PlanFeatureChecker` service mengecek limit (`max_members`, dll) sebelum aksi seperti invite anggota baru dijalankan.

## Rekomendasi Harga

Referensi kompetitor tidak langsung (single-user finance app Indonesia): Finansialku Rp35.000/bulan atau Rp350.000/tahun; Money Lover item berbayar Rp3.500-Rp439.000.

Produk ini charge **per-tenant, bukan per-user** — karena satu tenant dipakai bareng-bareng, charge per-user akan menghambat adopsi fitur kolaborasi yang jadi nilai jual utama.

| Tier | Harga | Fitur |
| --- | --- | --- |
| Free | Rp0 | Subdomain acak, maks 2-3 anggota, limit \~3 akun/kantong, tanpa export laporan |
| Pro | Rp25.000-Rp45.000/bulan (atau Rp250.000-Rp400.000/tahun, diskon \~20%) | Custom subdomain, unlimited anggota & kategori & akun, export laporan (PDF/Excel) |

Catatan strategi: uji harga rendah dulu di soft launch (harga bisa naik untuk user baru nanti, existing user grandfather di harga lama), validasi angka lewat survei kecil ke calon user (RT/komunitas terkait project sebelumnya).

## Stack & Library yang Digunakan

| Kebutuhan | Pilihan |
| --- | --- |
| Framework | Laravel + Inertia.js + Vue 3 |
| Multi-tenancy (domain resolution) | `stancl/tenancy` — dipakai untuk fitur domain identification saja, bukan database-per-tenant |
| Generator subdomain ramah dibaca | `atrox/haikunator` |
| Payment/subscription | Xendit (Recurring Payment API) |
| Otorisasi | Laravel Policy per model + global scope Eloquent (`BelongsToTenant` trait) |
| Login | Laravel Socialite (Google OAuth) |

## Open Questions / Next Steps

- [ ] Validasi angka harga tier Pro lewat survei ke calon user
- [ ] Tentukan urutan development phase 1 (mana dibangun duluan)
- [ ] Desain wireframe mobile-first untuk fitur inti (akun, transaksi, transfer)
- [ ] Tentukan channel notifikasi invite selain email (WhatsApp API?)
- [ ] Detail feature-gating per plan (`features` json di tabel `plans`) — finalisasi daftar fitur yang dibatasi

## Roadmap Phase 2

### Category Allocations (Menggantikan Budget & Proyeksi Terpisah)

Satu tabel `category_allocations` melayani dua kebutuhan: budget tracking bulan berjalan DAN baseline proyeksi ke depan — menggantikan rencana tabel `budgets` dan `projection_overrides` yang terpisah.

```
category_allocations
- id
- tenant_id, category_id, account_id (nullable)
- amount
- type (enum: standing, one_time)
- effective_from (date, nullable)   // wajib untuk type=standing
- period_month (date, nullable)     // wajib untuk type=one_time
- created_by
- timestamps
```

- **standing**: alokasi baku, berlaku sejak `effective_from` sampai ada standing baru yang menggantikan.
- **one\_time**: override khusus satu bulan, tidak mengubah baseline standing.

Resolution order per kategori per bulan: (1) one\_time untuk bulan tsb → menang, (2) kalau tidak ada, ambil standing terbaru yang `effective_from <= bulan tsb`.

Edit standing baru default berlaku **mulai bulan depan** (bukan langsung), kecuali user pilih eksplisit "berlaku sekarang" — supaya budget tracking bulan berjalan tidak berubah di tengah jalan tanpa disadari. Index dibutuhkan: `(tenant_id, category_id, type, effective_from)` dan `(tenant_id, category_id, type, period_month)`.

Di UI, disebut satu istilah saja ("Alokasi Kategori"), bukan dua menu terpisah "budget" vs "proyeksi".

### Recurring Transaction — Expand Logic

Cicilan digabung ke `recurring_transactions` (bukan tabel terpisah) — cicilan adalah recurring dengan `total_occurrences` terbatas.

```
recurring_transactions (kolom kunci)
- frequency, interval_count
- anchor_day, anchor_month     // tanggal asli yang diinginkan, dipakai untuk hitung ulang tiap occurrence — mencegah drift kalender
- total_occurrences (nullable) // NULL = tanpa batas; diisi untuk cicilan
- occurrences_completed
- end_date (nullable)
- is_active
```

**Prinsip kunci:** hitung tiap occurrence dari `anchor_day` asli, bukan dari occurrence sebelumnya — mencegah drift (misal recurring tanggal 31 "terjebak" di tanggal 28 selamanya setelah lewat Februari).

```php
protected function addMonthsSafe(Carbon $from, int $intervalCount, int $anchorDay): Carbon
{
    $target = $from->copy()->addMonths($intervalCount)->startOfMonth();
    $day = min($anchorDay, $target->daysInMonth);
    return $target->day($day);
}
```

`occurrences_completed` hanya bertambah saat draft **dikonfirmasi** jadi transaksi (bukan saat proyeksi dihitung — proyeksi read-only, tidak mengubah state). Fungsi expand yang sama (`occurrencesBetween()`) dipakai baik oleh job harian (generate draft hari ini) maupun projection engine (simulasi N bulan ke depan) — satu sumber kebenaran kalender, mencegah inkonsistensi.

Recurring yang diedit nominalnya tidak mengubah transaksi historis yang sudah confirmed — hanya memengaruhi proyeksi ke depan.

### Projection / Proyeksi Keuangan

Proyeksi dihitung **on-the-fly**, tidak disimpan sebagai baris transaksi (data belum terjadi).

Precedence per kategori per bulan proyeksi:

1. Recurring transaction aktif (termasuk cicilan) yang jatuh di bulan tsb → dijumlah per kategori
2. `resolveAllocation()` dari `category_allocations`, hanya untuk kategori yang **belum** tertutup oleh recurring

Tampilan yang paling berguna: proyeksi saldo per akun (line chart 3-6 bulan ke depan, menandai kalau ada akun diproyeksikan minus), breakdown per bulan per kategori (tabel editable via one\_time override), dan indikator sumber angka per baris (cicilan/recurring/alokasi).

Horizon proyeksi dibatasi 6-12 bulan — semakin jauh semakin tidak akurat. Permission set override konsisten dengan alokasi kategori: owner/admin saja.

### Dashboard Analytics

Visualisasi utama: cash flow bulanan (pemasukan vs pengeluaran beberapa bulan terakhir), breakdown pengeluaran per kategori bulan berjalan, trend saldo per akun, perbandingan periode (bulan ini vs bulan lalu).

Query agregat di-cache (15-30 menit, invalidate saat ada transaksi baru) karena dashboard diakses sering. Untuk tenant dengan histori panjang, pertimbangkan tabel `monthly_summaries` (precomputed) sebagai optimisasi lanjutan jika query on-the-fly mulai lambat.

View dashboard tetap terbuka untuk semua role (konsisten dengan permission viewAny phase 1) — hanya aksi edit/delete yang dibatasi per role.

### Kartu Kredit / PayLater

**Struktur tabel:** `accounts` tetap sebagai tabel utama (tanpa kolom khusus CC), kolom spesifik kartu kredit dipisah ke companion table one-to-one — menghindari polymorphic relation di `transactions`/`transfers` (yang akan merumitkan semua query sejak phase 1) dan menghindari tabel `accounts` jadi gemuk dengan kolom nullable untuk mayoritas row.

```
accounts (tidak berubah dari phase 1)

credit_card_details (baru, one-to-one dengan accounts)
- account_id (FK, unique)
- billing_cycle_start_day, billing_cycle_end_day
- payment_due_offset_days
- default_interest_rate_monthly, default_admin_fee_percentage
- credit_limit
```

Untuk tipe akun `credit_card`/`paylater`, arah balance terbalik — transaksi expense **menambah** balance (utang naik), bukan mengurangi.

**Billing cycle resolver** — menentukan periode tagihan (`statement_period_end`) dan jatuh tempo (`due_date`) dari tanggal transaksi:

```php
class CreditCardBillingResolver
{
    public function resolvePeriod(Account $account, Carbon $transactionDate): array
    {
        $detail = $account->creditCardDetail;
        $endDay = $detail->billing_cycle_end_day; // misal 15

        $periodEnd = $transactionDate->day > $endDay
            ? $transactionDate->copy()->addMonthNoOverflow()->day($endDay)
            : $transactionDate->copy()->day($endDay);

        $periodStart = $periodEnd->copy()->subMonthNoOverflow()->addDay();
        $dueDate = $periodEnd->copy()->addDays($detail->payment_due_offset_days);

        return compact('periodStart', 'periodEnd', 'dueDate');
    }
}
```

Contoh: CC dengan cycle 16-15, transaksi tanggal 19 September → `periodEnd` = 15 Oktober (masuk statement Oktober), `dueDate` = periodEnd + offset (jatuh tempo pembayaran).

**Perhitungan cicilan/non-cicilan** — bunga flat + admin fee, dihitung otomatis saat input transaksi expense dengan sumber kartu kredit:

```php
class InstallmentCalculator
{
    public function calculate(float $principal, int $tenorMonths, float $interestRateMonthly, float $adminFee): array
    {
        $totalInterest = $principal * ($interestRateMonthly / 100) * $tenorMonths;
        $totalAmount = $principal + $totalInterest + $adminFee;
        $monthlyAmount = round($totalAmount / $tenorMonths, -2);
        $lastMonthAdjustment = $totalAmount - ($monthlyAmount * ($tenorMonths - 1));

        return compact('totalInterest', 'totalAmount', 'monthlyAmount', 'lastMonthAdjustment');
    }
}
```

Non-cicilan (bayar penuh bulan depan): `tenor_months = 1`, admin fee tetap dihitung kalau ada, tanpa bunga.

**Pembulatan cicilan terakhir bisa diedit manual** oleh user di form edit recurring — kolom `last_installment_adjustment_overridden` (bool) menandai kalau user sudah override, supaya sistem tidak re-kalkulasi otomatis.

**Integrasi ke recurring\_transactions** (kolom tambahan, tanpa mengubah expand logic yang sudah ada):

```
recurring_transactions (tambahan untuk sumber kartu kredit)
- source_transaction_id (FK, nullable)
- principal_amount, interest_rate_monthly, admin_fee_amount, total_amount
- statement_period_end (date, nullable)   // info "masuk tagihan bulan apa"
- due_date (date, nullable)                // dipakai sebagai anchor proyeksi cash flow
- last_installment_adjustment (nullable)
- last_installment_adjustment_overridden (bool, default false)
```

`anchor_day` cicilan CC diambil dari `due_date` hasil billing resolver, bukan tanggal transaksi pembelian — supaya occurrence berikutnya ikut siklus tagihan kartu, bukan tanggal beli barang.

**Pelunasan tagihan** dicatat sebagai transaksi tipe `bill_payment` (bukan transfer biasa), supaya muncul di histori kartu kredit tsb:

```
transactions (tambahan)
- type (+ bill_payment)
- linked_account_id (FK accounts, nullable)   // akun sumber pembayaran, khusus type=bill_payment
```

`bill_payment` mengurangi balance akun kartu kredit (utang berkurang) dan balance `linked_account_id` (uang bank berkurang) dalam satu DB transaction.

**Proyeksi menampilkan `statement_period_end` dan `due_date` sekaligus** — cash flow chart pakai `due_date` (uang riil keluar) sebagai penentu bulan proyeksi, `statement_period_end` jadi info tambahan di tooltip/detail ("Tagihan Oktober, jatuh tempo 15 Nov").

### Realisasi Proyeksi & Variance (`plan_items`)

Menyatukan konsep draft recurring (dari phase 2 awal) dan item proyeksi jadi satu tabel — item proyeksi bisa ditindaklanjuti langsung jadi transaksi.

```
plan_items
- id
- tenant_id, period_month, category_id, account_id (nullable)
- source_type (enum: recurring, allocation)
- source_id (FK, nullable — recurring_transactions.id atau category_allocations.id)
- planned_amount
- due_date (nullable)
- status (enum: pending, realized, skipped)
- realized_transaction_id (FK transactions, nullable)
- realized_amount (nullable)
  unique(tenant_id, source_type, source_id, period_month)
```

**Hanya `source_type = recurring`** yang punya status pending/realized/skipped dan tombol "Tandai Terealisasi". Untuk `source_type = allocation` (budget kategori Makan, Transport), realisasi bersifat akumulatif dari transaksi yang masuk sepanjang bulan — ditampilkan sebagai progress bar, bukan status biner.

**Generate plan\_items** dari expand logic recurring (job harian untuk bulan berjalan, atau lazy saat user buka halaman proyeksi), pakai `firstOrCreate` dengan unique constraint agar tidak duplikat.

**Flow "Tandai Terealisasi":** klik tombol pada plan\_item → muncul form transaksi biasa (bukan form khusus), pre-filled dari plan\_item (account, category, amount, description), user bisa edit sebelum submit. Setelah submit: buat row `transactions` baru (lewat flow normal phase 1, termasuk update balance), lalu update plan\_item (`status=realized`, `realized_transaction_id`, `realized_amount`), dan increment `occurrences_completed` pada recurring terkait.

Karena pakai form transaksi normal, semua validasi/policy/balance-update phase 1 otomatis terpakai tanpa duplikasi logic. Permission `markRealized` disamakan dengan permission create transaction (owner/admin/member semua boleh) — bukan permission level recurring (owner/admin saja), karena aksinya adalah mencatat transaksi.

**Variance (selisih proyeksi vs aktual):**

- Item diskrit (recurring, realized): `variance = realized_amount - planned_amount`, ditampilkan misal "Listrik — Diperkirakan Rp350.000, Aktual Rp380.000 (+Rp30.000)".
- Item agregat (allocation): `variance = SUM(transactions kategori tsb bulan ini) - allocation_amount`, ditampilkan sebagai progress bar ("Makan — Rp1.650.000/Rp2.000.000, 82%") yang update otomatis tiap transaksi baru.

**Edge case yang perlu tombol tambahan:** opsi "Lewati" (status `skipped`) untuk item yang ternyata tidak terjadi (misal listrik digratiskan bulan itu) — tidak masuk hitungan variance.

**Tidak ada auto-match** antara transaksi manual dan plan\_item terkait di phase 2 — user harus eksplisit klik "Tandai Terealisasi" untuk mengaitkan. Auto-matching heuristik jadi kandidat phase 3 kalau ternyata dibutuhkan.

## Roadmap Phase 3

Fokus: menambang lebih dalam data yang sudah ada di phase 1-2 — tidak ada biaya operasional baru per-pemakaian.

- **Activity log / audit trail** — data sudah ada (`created_by`, timestamps), tinggal bangun UI
- **Net worth tracking** — agregasi akun (aset) dikurangi kartu kredit/paylater (liability) jadi satu angka kekayaan bersih per tenant, trend dari waktu ke waktu
- **Debt payoff strategy** — kalkulator snowball (lunasi terkecil dulu) / avalanche (lunasi bunga tertinggi dulu) dari data cicilan yang sudah terstruktur
- **Credit utilization tracking** — persentase pemakaian `credit_limit`, alert saat mendekati limit
- **Auto-matching plan\_item ke transaksi manual** — matching heuristik berdasarkan category\_id + account\_id + range tanggal + kemiripan amount, yang sengaja ditunda dari phase 2
- **Approval workflow** — transaksi di atas nominal tertentu butuh approval owner/admin dulu, extend permission system yang sudah ada
- **Allowance/spending limit per anggota** — extend konsep `category_allocations` untuk limit personal, bukan cuma limit kategori
- **Reminder notifikasi** — untuk plan\_item mendekati due\_date yang masih pending
- **Laporan gaya akuntansi** — cash flow statement per periode, exportable

## Roadmap Phase 4

Fokus: fitur yang butuh infrastruktur atau biaya operasional baru yang skalanya proporsional dengan pemakaian — butuh keputusan bisnis (pricing, cost control) terpisah.

### Phase 4a — AI-Assisted Input (Receipt OCR & Chat Parsing)

**Keputusan platform: AI disediakan platform (API key di backend), bukan BYOK.** Target user (keluarga/RT/komunitas) mayoritas tidak familiar dengan konsep API key — BYOK akan membuat fitur ini efektif tidak terpakai oleh mayoritas user.

**Receipt OCR:** upload foto struk → OCR extract merchant/amount/tanggal → tebak kategori (rule-based dari `merchant_category_mappings` histori tenant dulu, fallback ke AI kalau tidak ketemu) → **selalu tampil sebagai form pre-filled untuk konfirmasi user, tidak pernah auto-save**.

**Pencatatan via chat:** user ketik bebas → coba rule-based regex untuk pola umum dulu → fallback ke AI untuk parsing bebas kalau tidak match → balas ringkasan + konfirmasi (atau pertanyaan klarifikasi kalau ambigu) → user konfirmasi baru tersimpan.

**Implikasi teknis:**

- Panggilan AI selalu dari backend (Laravel), API key tidak pernah terekspos ke client
- Kuota per plan tier, extend `PlanFeatureChecker`: `plans.features.ai_receipt_scans_per_month`, `ai_chat_messages_per_month` — fitur eksklusif tier Pro/Business karena ada biaya langsung per pemakaian
- Fallback bertingkat wajib (rule-based dulu, AI cuma untuk kasus yang tidak match) — mengurangi biaya API dan latensi
- Hasil klasifikasi AI disimpan ke `merchant_category_mappings` supaya merchant yang sama besok tidak perlu panggil AI lagi
- Keamanan channel chat (verifikasi nomor WA terdaftar ke user/tenant yang benar) wajib sebelum memproses pesan

```
merchant_category_mappings (baru)
- id, tenant_id, merchant_name, category_id, timestamps
```

### Phase 4b — Investment Tracking & Import Bank

- **Investment tracking** — instrumen investasi (saham/reksadana/kripto) punya valuasi yang berubah tanpa transaksi eksplisit, beda fundamental dari kantong biasa — butuh model data terpisah (`investments`, `investment_valuations` dengan histori harga) dan kemungkinan integrasi API harga eksternal. Didalami sebagai sesi terpisah saat mendekati waktu implementasi.
- **Import mutasi bank/e-wallet (CSV)** — tiap provider beda format, maintenance berkelanjutan tiap kali provider ubah format export
- **Multi-currency** — ditunda sampai ada demand jelas dari user

## Referensi Lengkap: Daftar Tabel & Kolom

Konsolidasi seluruh tabel yang sudah dibahas, phase 1 sampai phase 4.

### Core & Tenant

```
users
- id, name, email (unique), google_id (unique, nullable)
- avatar, password (nullable)
- timestamps

tenants
- id, name, subdomain (unique), is_custom_subdomain (bool)
- owner_id (FK users)
- timestamps

domains
- id, domain, tenant_id (FK)
- redirects_to (nullable — untuk redirect subdomain lama saat ganti custom)
- timestamps

tenant_user (pivot)
- id, tenant_id (FK), user_id (FK), role (enum: owner, admin, member)
- joined_at, timestamps
  unique(tenant_id, user_id)

tenant_invitations
- id, tenant_id (FK), email, role (enum: admin, member)
- token (unique), invited_by (FK users)
- status (enum: pending, accepted, expired, revoked), expires_at
- timestamps
```

### Data Finansial Inti (Phase 1)

```
accounts ("kantong")
- id, tenant_id (FK), name
- type (enum: cash, bank, ewallet, credit_card, paylater, other)
- balance (decimal 15,2) — cached, di-update via DB transaction
- icon, color (nullable), is_archived (bool)
- created_by (FK users), timestamps

categories
- id, tenant_id (FK), name, type (enum: income, expense)
- icon (nullable), is_default (bool)
- timestamps

transactions
- id, tenant_id (FK), account_id (FK accounts)
- category_id (FK categories, nullable — kosong untuk bill_payment)
- type (enum: income, expense, bill_payment)
- linked_account_id (FK accounts, nullable — khusus type=bill_payment, akun sumber pembayaran)
- amount (decimal 15,2), description (nullable), transaction_date
- created_by (FK users)
- soft deletes, timestamps

transfers
- id, tenant_id (FK)
- from_account_id, to_account_id (FK accounts)
- amount (decimal 15,2), description (nullable), transfer_date
- created_by (FK users)
- soft deletes, timestamps
```

### Kartu Kredit / PayLater (Phase 2)

```
credit_card_details (one-to-one dengan accounts)
- id, account_id (FK, unique)
- billing_cycle_start_day, billing_cycle_end_day (1-31)
- payment_due_offset_days
- default_interest_rate_monthly, default_admin_fee_percentage
- credit_limit (nullable)
- timestamps
```

### Budgeting & Recurring (Phase 2)

```
category_allocations (menggantikan konsep budgets terpisah)
- id, tenant_id (FK), category_id (FK)
- account_id (FK, nullable)
- amount
- type (enum: standing, one_time)
- effective_from (date, nullable — wajib untuk type=standing)
- period_month (date, nullable — wajib untuk type=one_time)
- created_by (FK users), timestamps
  unique(tenant_id, category_id, account_id, period_month) — untuk one_time

recurring_transactions (termasuk cicilan & tagihan kartu kredit)
- id, tenant_id (FK), account_id (FK), category_id (FK)
- type (enum: income, expense)
- amount, description
- frequency (enum: daily, weekly, monthly, yearly), interval_count
- anchor_day, anchor_month — tanggal asli untuk hitung ulang tiap occurrence (cegah drift kalender)
- next_run_date
- total_occurrences (nullable — NULL = tanpa batas, diisi untuk cicilan)
- occurrences_completed
- end_date (nullable)
- is_active (bool)
- source_transaction_id (FK, nullable — transaksi pembelian asli untuk cicilan CC)
- principal_amount, interest_rate_monthly, admin_fee_amount, total_amount (nullable — khusus cicilan)
- statement_period_end (date, nullable — info "masuk tagihan bulan apa")
- due_date (date, nullable — dipakai sebagai anchor proyeksi cash flow)
- last_installment_adjustment (nullable)
- last_installment_adjustment_overridden (bool, default false)
- created_by (FK users), timestamps
```

### Proyeksi & Realisasi (Phase 2)

```
plan_items
- id, tenant_id (FK), period_month, category_id (FK)
- account_id (FK, nullable)
- source_type (enum: recurring, allocation)
- source_id (FK, nullable — recurring_transactions.id atau category_allocations.id)
- planned_amount
- due_date (nullable)
- status (enum: pending, realized, skipped)
- realized_transaction_id (FK transactions, nullable)
- realized_amount (nullable)
- timestamps
  unique(tenant_id, source_type, source_id, period_month)
```

### Billing & Subscription (Phase 1)

```
plans
- id, name, slug, price, billing_period (enum: monthly, yearly)
- features (json — ai_receipt_scans_per_month, ai_chat_messages_per_month, max_members, dst)
- is_active, timestamps

subscriptions
- id, tenant_id (FK), plan_id (FK)
- status (enum: active, past_due, cancelled, expired)
- xendit_customer_id, xendit_subscription_id
- current_period_start, current_period_end
- cancelled_at (nullable), timestamps
```

### AI-Assisted Input (Phase 4a)

```
merchant_category_mappings
- id, tenant_id (FK), merchant_name, category_id (FK)
- timestamps
```

### Belum Didetailkan (Phase 4b, Menunggu Sesi Terpisah)

```
investments — model data untuk instrumen dengan valuasi berubah (saham/reksadana/kripto)
investment_valuations — histori harga per waktu
```
