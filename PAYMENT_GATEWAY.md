# Payment Gateway (Billing Fluxa)

Langganan Fluxa tidak terikat ke satu provider. Ganti provider (Xendit,
Midtrans, Duitku, ...) berarti: tulis satu adapter baru + ganti satu nilai
config. Inti billing — action, controller, halaman, webhook handler — tidak
berubah.

## Arsitektur

```
                 ┌─────────────────────────┐
                 │     config/billing.php  │  BILLING_GATEWAY=log|xendit|...
                 └────────────┬────────────┘
                              │ resolve (AppServiceProvider)
                              ▼
                 ┌─────────────────────────┐
                 │   PaymentGateway (iface)│
                 └────────────┬────────────┘
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
       XenditGateway     LogGateway     MidtransGateway (contoh §4)
              │               │
              └───────┬───────┘
                      ▼
         HandleGatewayWebhookAction
         (event ternormalisasi → status)
```

Kontrak ada di `app/Billing/`:

| File                   | Isi                                                                                   |
| ---------------------- | ------------------------------------------------------------------------------------- |
| `PaymentGateway.php`   | Interface: customer, recurring, cancel, verifikasi webhook, parse event, id eksternal |
| `GatewayEvent.php`     | Event ternormalisasi: activated, payment succeeded/failed, stopped, unknown           |
| `GatewayCheckout.php`  | Hasil createRecurring: URL checkout + id eksternal                                    |
| `GatewayException.php` | Gagal panggil provider (bukan salah input user)                                       |
| `XenditGateway.php`    | Satu-satunya tempat yang kenal API Xendit                                             |
| `LogGateway.php`       | Tanpa provider: catat ke log, id palsu. Untuk lokal + bukti kontrak                   |

Aturan keras: **inti billing tidak boleh menyebut nama provider.** Nama event
Xendit/Midtrans hanya boleh muncul di dalam adapter masing-masing.

## Skema data

`subscriptions` memakai kolom generik (bukan `xendit_*`):

- `gateway` — nama kanonis adapter (`xendit`, `log`, ...), diisi dari
  `$gateway->name()` saat baris dibuat
- `external_customer_id`, `external_subscription_id` — id di sisi provider,
  dipakai mencocokkan webhook

## Alur

### Upgrade (mock saat ini)

`BillingController@store` → `ToggleSubscriptionAction` bolak-balik
free ↔ pro-monthly tanpa memanggil provider. Saat kunci tersedia, alurnya
menjadi: `createCustomer` → `createRecurring` → redirect ke
`checkoutUrl` → provider callback ke webhook di bawah.

### Webhook (`POST billing/webhook`, central domain)

Tanpa auth dan tanpa CSRF (provider tidak bisa kirim keduanya). Satu-satunya
pertahanan adalah verifikasi signature adapter (`verifyWebhook`), lalu:

1. `parseEvent` → `GatewayEvent`
2. `externalSubscriptionId` → cari baris (`gateway` + id eksternal, di luar
   tenancy)
3. Terapkan status: activated → active (+ periode baru), succeeded → active
   (+ perpanjang periode), failed → past_due, stopped → cancelled
4. Event tak dikenal atau id tak cocok: catat + jawab 200 agar provider
   tidak retry tanpa henti. Tanda palsu: 403.

## Menambah provider baru (contoh: Midtrans)

1. Buat `app/Billing/MidtransGateway.php` implements `PaymentGateway`
   (`name()` mengembalikan `'midtrans'`).
2. Petakan: customer → Midtrans API, recurring → subscription Midtrans,
   `verifyWebhook` → cek `signature_key`
   (`hash('sha512', order_id.status_code.gross_amount.server_key)`),
   `parseEvent` → `transaction_status` (`settlement`/`capture` → succeeded,
   `deny`/`expire`/`cancel` → failed, ...).
3. Daftarkan di `AppServiceProvider`: tambah satu arm `match`.
4. Tambah kunci di `config/billing.php` + `.env.example`.
5. Tulis test seperti `GatewayTest` (fake HTTP + webhook 403/200).
6. Ganti `BILLING_GATEWAY=midtrans`. Selesai — tidak ada migrasi, tidak ada
   perubahan inti.

## Konfigurasi

| Env                     | Default                 | Keterangan                         |
| ----------------------- | ----------------------- | ---------------------------------- |
| `BILLING_GATEWAY`       | `log`                   | `xendit`, `log`, atau adapter baru |
| `XENDIT_SECRET_KEY`     | –                       | Wajib bila gateway `xendit`        |
| `XENDIT_CALLBACK_TOKEN` | –                       | Verifikasi webhook Xendit          |
| `XENDIT_BASE_URL`       | `https://api.xendit.co` | Override untuk staging/mock        |

Nama gateway tak dikenal membuat resolve gagal keras saat boot, supaya salah
ketik ketahuan saat deploy, bukan saat webhook masuk.

## Lokal

- Default `log`: semua panggilan dicatat, tidak ada trafik keluar.
- `PlanSeeder` wajib jalan (`php artisan db:seed --class=PlanSeeder`).
- Tenant tanpa langganan otomatis dibuatkan paket free saat halaman billing
  dibuka.

## Test

`tests/Feature/Billing/GatewayTest.php`: binding config, adapter Xendit
dengan `Http::fake`, tolak tanpa kunci/token, webhook 403, transisi status,
abaikan event/id asing. Adapter baru wajib membawa test setara.
