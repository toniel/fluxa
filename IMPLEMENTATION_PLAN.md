# Rencana Implementasi Prototype "Fluxa"

Multi-tenant financial management SaaS — Laravel 13 + Inertia 3 + Vue 3.5 + Tailwind 4.
Repo: `/home/toni2/Sites/fluxa` (branch `master`, belum ada commit sama sekali).

---

## 0. Keputusan Arsitektur Utama (baca ini dulu)

### 0.1 Konvensi CRUD — diadopsi penuh dari `CRUD_FLOW.md` (perubahan 26 Sep 2026)

Keputusan awal bagian ini — opsi **(b+)**: FormRequest + JsonResource, JANGAN
install stack `CRUD_FLOW.md` — **sudah digantikan.** Sejak keputusan itu
ditulis, stack `CRUD_FLOW.md` ternyata sudah terpasang di `composer.json`
(`spatie/laravel-data`, `spatie/laravel-permission`,
`spatie/laravel-typescript-transformer`, `lacodix/laravel-model-filter`,
`lorisleiva/laravel-actions`, `laravel/wayfinder`) dan
`resources/js/generated/generated.d.ts` sudah di-commit. Karena stack-nya ada,
aturan-aturannya juga dibawa utuh dari aiu-alumni: `CRUD_FLOW.md`,
`tests/Unit/ConventionsTest.php`, `bin/no-zero-coverage.php`, floor coverage
90%, `composer types:sync` — semuanya.

Kenapa opsi (a) penuh sekarang menang:

1. **Biaya "install 5 package + npm" sudah lunas.** Argumen pembunuh opsi (b+)
   dulu adalah hari kerja sebelum satu fitur ditulis; itu sudah terbayar oleh
   package yang terpasang. Yang tersisa hanya mengikuti pola yang dokumentasi
   dan test-nya juga sudah diport.
2. **spatie/permission sudah dipakai.** `User` memakai `HasRoles`, enum
   `PermissionEnum` sudah ada, dan kekhawatiran lama soal "teams feature
   bertabrakan dengan tenant scoping" dijawab bukan dengan menghindari package,
   tapi dengan membiarkan scoping tenant tetap di global scope dan role
   per-tenant tetap di data + helper `Tenant::hasRole()`. Permission menjawab
   "role boleh aksi jenis apa"; baris milik siapa tetap dijawab Policy.
3. **Satu pola lebih murah diverifikasi.** Dua pola (FormRequest+JsonResource di
   satu belahan, Data object di belahan lain) membuat konvensi tidak bisa
   diuji satu aturan. `ConventionsTest` menguji `app/Data` dan tidak tahu
   apa-apa tentang JsonResource — itulah argumen untuk memilih satu.
4. **TanStack Query tetap tidak dipakai.** Konvensi 3 `CRUD_FLOW.md` hanya
   mewajibkan `axios` terkunci di `resources/js/services/`. Selama frontend
   memakai Inertia props, itu hijau trivially; lapisan `Api/` JSON bisa datang
   belakangan lewat `services/` tanpa mengubah pola halaman.

Jadi yang DIADOPSI sekarang (diperbarui):

| Aturan CRUD_FLOW                                                   | Adopsi | Catatan                                                                                 |
| ------------------------------------------------------------------ | ------ | --------------------------------------------------------------------------------------- |
| `#[Fillable]` di model                                             | Ya     | Native Laravel 13; sudah dipakai `User`, `Tenant`                                       |
| Query logic ke `app/QueryBuilders/` + `#[UseEloquentBuilder]`      | Ya     | Native; disepakati sejak awal                                                           |
| Write logic ke `app/Actions/`                                      | Ya     | `lorisleiva/laravel-actions` terpasang → pola `UpsertXAction` (`AsAction`)              |
| Policy + `Gate::authorize()`, didaftar `#[UsePolicy]`              | Ya     | Native; role dibaca `TenantContext::role()` (0 query)                                   |
| `denyAsNotFound()` untuk baris yang tak boleh dikonfirmasi         | Ya     | Penting untuk resource yang tidak ber-global-scope: members, invitations, tenant switch |
| Semua URL dari Wayfinder, `--with-form`                            | Ya     | Sudah terpasang, `formVariants: true` di `vite.config.ts`                               |
| `Inertia::flash('toast', ...)`                                     | Ya     | `resources/js/lib/flashToast.ts` sudah ada                                              |
| Pola `Form.vue` + `Create.vue`/`Edit.vue`                          | Ya     | Gratis                                                                                  |
| File `.vue` ≤ 300 baris                                            | Ya     | Enforced; 3 file pratinjau dibaseline di `ConventionsTest`                              |
| 2 Data object per resource (`XData`/`XFormData`) + `#[TypeScript]` | Ya     | `spatie/laravel-data` + typescript-transformer terpasang                                |
| lacodix `IsSearchable`/`IsSortable`/`HasFilters`                   | Ya     | Package terpasang; `filterFromRequest()` di §2.6 **dibatalkan**                         |
| `ConventionsTest` + coverage 90% + `bin/no-zero-coverage.php`      | Ya     | Enforcement diport dari aiu-alumni                                                      |
| TanStack Query + `Api/` controller + `services/`                   | Belum  | `axios` hanya boleh di `services/`; selama Inertia props dipakai, aturan ini hijau      |
| spatie/permission role per-tenant                                  | Ya     | `PermissionEnum` + `Tenant::hasRole()`; scoping tenant tetap global scope               |

**Dampak ke bagian lain dokumen ini:** referensi yang menyebut `JsonResource`
(§5.4), `filterFromRequest()` pada QueryBuilder (§2.6), dan "2 Spatie Data TIDAK"
diintro jadi usang. Ganti mentalnya begini: **outbound memakai `XData`, inbound
memakai `XFormData`, keduanya `#[TypeScript]`; `can_edit`/`can_delete`
dihitung di Policy dan disisipkan saat collect** — bukan di JsonResource.
`CRUD_FLOW.md` di repo root adalah acuan pola yang berlaku; dokumen ini tetap
acuan rencana tenancy, integritas balance, dan fase pembangunan.

### 0.2 Keputusan teknis lain yang mengikat seluruh rencana

| Topik                      | Keputusan                                                                           | Alasan singkat                                                                                                                                   |
| -------------------------- | ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Tipe kolom enum            | `string(32)` + PHP backed enum di `app/Enums/` + cast, **bukan** `$table->enum()`   | Phase 2 menambah `bill_payment`/`credit_card` cukup ubah enum PHP, tanpa `ALTER TABLE` (yang menyakitkan di MySQL dan tak didukung penuh SQLite) |
| Mutasi balance             | **Action/Service, bukan Observer**                                                  | Detail di §3                                                                                                                                     |
| Tenant context             | Singleton scoped + middleware + session key                                         | Detail di §2                                                                                                                                     |
| `transactions.category_id` | **nullable sejak sekarang**                                                         | Phase 2 `bill_payment` tidak punya kategori; nullable sejak awal = nol data migration nanti                                                      |
| Chart                      | SVG tulis tangan (tanpa dependency)                                                 | Detail di §9.3                                                                                                                                   |
| Format Rupiah              | Angka mentah dari backend, format di frontend                                       | Detail di §9.4                                                                                                                                   |
| ID                         | `bigIncrements` (bukan ULID)                                                        | Starter kit `users` sudah `$table->id()`; konsistensi > estetika untuk prototype                                                                 |
| Uang                       | `decimal(15,2)`, cast `decimal:2`, aritmetika lewat `increment`/`decrement` DB-side | Hindari float; PRD sudah menetapkan `decimal(15,2)`                                                                                              |

---

## 1. Fase 1 — Migrations

Semua di `database/migrations/`. Urutan timestamp harus menjaga urutan FK.

### 1.1 `2026_09_19_100000_add_google_columns_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique()->after('email');
    $table->string('avatar')->nullable()->after('google_id');
    $table->string('password')->nullable()->change();
});
```

`down()` harus drop unique index dulu sebelum drop kolom (SQLite rewel). Laravel 11+ tidak lagi butuh `doctrine/dbal` untuk `->change()`.

### 1.2 `..._100100_create_tenants_table.php`

```php
$table->id();
$table->string('name');
$table->string('subdomain')->unique();          // placeholder, belum dipakai untuk routing
$table->boolean('is_custom_subdomain')->default(false);
$table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
$table->timestamps();
```

`subdomain` diisi `Str::slug($name).'-'.Str::lower(Str::random(4))` oleh `CreateTenantAction`. Kolom tetap ada + unique supaya migrasi ke domain identification nanti tinggal menambahkan tabel `domains` dan middleware, tanpa menyentuh data.

### 1.3 `..._100200_create_tenant_user_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->string('role', 16);                      // TenantRole: owner|admin|member
$table->timestamp('joined_at')->nullable();
$table->timestamps();
$table->unique(['tenant_id', 'user_id']);
$table->index(['user_id', 'tenant_id']);         // untuk daftar tenant milik user (switcher)
```

### 1.4 `..._100300_create_tenant_invitations_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->string('email');
$table->string('role', 16);                      // admin|member (owner tidak bisa diundang)
$table->string('token', 64)->unique();
$table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
$table->string('status', 16)->default('pending'); // pending|accepted|expired|revoked
$table->timestamp('expires_at');
$table->timestamps();
$table->unique(['tenant_id', 'email']);          // <- lihat catatan
$table->index('status');
```

**Catatan penting — menyimpang sedikit dari PRD.** PRD menulis `unique(tenant_id, email, status='pending')` (partial unique). Partial/filtered unique index tidak portabel (MySQL tidak punya, SQLite punya dengan sintaks berbeda, phpunit di repo ini jalan di SQLite `:memory:`). Karena requirement "invite ulang ke email yang sama → update row yang ada, bukan duplikat" justru **lebih mudah** dengan unique penuh, pakai `unique(['tenant_id','email'])` + `updateOrCreate(['tenant_id' => ..., 'email' => ...], [...])`. Konsekuensi yang diterima: satu email hanya punya satu baris riwayat undangan per tenant (undangan lama ditimpa). Untuk prototype ini benar; kalau audit trail undangan dibutuhkan, itu masuk risks (§12).

### 1.5 `..._100400_create_accounts_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->string('type', 24);                      // AccountType: cash|bank|ewallet|other (+ credit_card|paylater phase 2)
$table->decimal('balance', 15, 2)->default(0);
$table->decimal('initial_balance', 15, 2)->default(0);
$table->string('icon', 64)->nullable();          // nama ikon Lucide, mis. 'wallet'
$table->string('color', 16)->nullable();         // token warna, mis. 'emerald'
$table->boolean('is_archived')->default(false);
$table->foreignId('created_by')->constrained('users');
$table->timestamps();
$table->index(['tenant_id', 'is_archived']);
```

`initial_balance` tidak ada di PRD tapi ada di requirement user ("initial balance"). Simpan keduanya: `initial_balance` = nilai yang diketik user saat create (immutable secara semantik), `balance` = saldo berjalan. Ini memberi jalan rekonsiliasi (`balance` harus = `initial_balance` + Σ mutasi) yang dipakai di test §11.

**Slot phase 2:** tabel ini tidak berubah sama sekali. `credit_card_details` masuk sebagai tabel one-to-one baru; `AccountType` cukup ditambah case.

### 1.6 `..._100500_create_categories_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->string('type', 16);                      // CategoryType: income|expense
$table->string('icon', 64)->nullable();
$table->boolean('is_default')->default(false);
$table->timestamps();
$table->unique(['tenant_id', 'type', 'name']);   // cegah kategori duplikat per tenant
$table->index(['tenant_id', 'type']);
```

### 1.7 `..._100600_create_transactions_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('account_id')->constrained()->cascadeOnDelete();
$table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
$table->string('type', 24);                      // TransactionType: income|expense (+ bill_payment phase 2)
$table->decimal('amount', 15, 2);
$table->text('description')->nullable();
$table->date('transaction_date');
$table->foreignId('created_by')->constrained('users');
$table->softDeletes();
$table->timestamps();
$table->index(['tenant_id', 'transaction_date']);
$table->index(['tenant_id', 'category_id', 'transaction_date']); // untuk summary dashboard
$table->index(['tenant_id', 'account_id']);
```

**Slot phase 2 (tanpa rewrite):** `linked_account_id` masuk lewat migration aditif `add_bill_payment_columns_to_transactions_table`. Karena `category_id` sudah nullable dan `type` sudah `string`, tidak ada perubahan struktur yang menyentuh baris lama. `transaction_date` sengaja `date`, bukan `dateTime` — menghindari jebakan timezone yang dibahas di `CRUD_FLOW.md` §6 sepenuhnya.

### 1.8 `..._100700_create_transfers_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('from_account_id')->constrained('accounts')->cascadeOnDelete();
$table->foreignId('to_account_id')->constrained('accounts')->cascadeOnDelete();
$table->decimal('amount', 15, 2);
$table->text('description')->nullable();
$table->date('transfer_date');
$table->foreignId('created_by')->constrained('users');
$table->softDeletes();
$table->timestamps();
$table->index(['tenant_id', 'transfer_date']);
```

### 1.9 `..._100800_create_plans_table.php`

```php
$table->id();
$table->string('name');
$table->string('slug')->unique();                // 'free', 'pro-monthly'
$table->decimal('price', 15, 2)->default(0);
$table->string('billing_period', 16);            // BillingPeriod: monthly|yearly
$table->json('features');                        // {"max_members":3,"max_accounts":3,"custom_subdomain":false,"export":false}
$table->boolean('is_active')->default(true);
$table->timestamps();
```

Tanpa `tenant_id` — tabel global, **tidak** pakai `BelongsToTenant`.

### 1.10 `..._100900_create_subscriptions_table.php`

```php
$table->id();
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->foreignId('plan_id')->constrained();
$table->string('status', 16);                    // SubscriptionStatus: active|past_due|cancelled|expired
$table->string('xendit_customer_id')->nullable();      // placeholder phase 1
$table->string('xendit_subscription_id')->nullable();  // placeholder phase 1
$table->timestamp('current_period_start')->nullable();
$table->timestamp('current_period_end')->nullable();
$table->timestamp('cancelled_at')->nullable();
$table->timestamps();
$table->index(['tenant_id', 'status']);
```

Satu subscription aktif per tenant; `CreateTenantAction` langsung membuat baris `free` + `active`.

---

## 2. Fase 2 — Models, Relasi, dan `BelongsToTenant`

### 2.1 Enums — `app/Enums/`

Semua backed string enum, masing-masing dengan `label(): string` (Bahasa Indonesia, untuk dropdown) dan `values(): array` static.

```
app/Enums/TenantRole.php          Owner='owner', Admin='admin', Member='member'
                                  + isAtLeast(self $role): bool   // owner > admin > member
app/Enums/AccountType.php         Cash, Bank, Ewallet, Other      // CreditCard, Paylater = phase 2
app/Enums/CategoryType.php        Income='income', Expense='expense'
app/Enums/TransactionType.php     Income, Expense                 // BillPayment = phase 2
                                  + signum(): int  // income=+1, expense=-1  <- dipakai balance mutator
app/Enums/InvitationStatus.php    Pending, Accepted, Expired, Revoked
app/Enums/SubscriptionStatus.php  Active, PastDue, Cancelled, Expired
app/Enums/BillingPeriod.php       Monthly, Yearly
```

`TransactionType::signum()` adalah satu-satunya tempat arah uang ditulis. Saat phase 2 menambah `credit_card` (arah balance terbalik), perubahannya terlokalisasi di sini + `AccountType::balanceDirection()`.

### 2.2 Tenant context — `app/Support/TenantContext.php`

```php
final class TenantContext
{
    private ?Tenant $tenant = null;
    private ?TenantRole $role = null;
    private bool $bypassed = false;

    public function set(Tenant $tenant, ?TenantRole $role = null): void;
    public function tenant(): ?Tenant;
    public function id(): ?int;
    public function role(): ?TenantRole;
    public function check(): bool;          // ada tenant aktif?
    public function forget(): void;

    /** Jalankan callback tanpa global scope (seeder, console, cross-tenant). */
    public function withoutScope(callable $callback): mixed;
    public function isBypassed(): bool;

    /** Jalankan callback seolah-olah tenant X aktif (accept invitation, test, job). */
    public function runFor(Tenant $tenant, callable $callback): mixed;
}
```

Di-bind di `AppServiceProvider::register()` sebagai **`scoped`** (bukan `singleton`) supaya aman kalau nanti pakai Octane:

```php
$this->app->scoped(TenantContext::class);
```

Facade opsional; lebih baik inject saja atau `app(TenantContext::class)`.

### 2.3 Global scope — `app/Models/Scopes/TenantScope.php`

```php
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isBypassed()) {
            return;
        }

        if ($context->check()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());
            return;
        }

        // Tidak ada tenant aktif.
        if (app()->runningInConsole()) {
            return;                       // seeder, migration, artisan, test factory
        }

        throw new TenantContextMissingException($model::class);
    }
}
```

**Ini jawaban untuk jebakan klasik.** Pola yang gagal adalah `if ($tenantId) { where(...) }` diam-diam — kalau tenant belum ter-set, query mengembalikan **seluruh baris semua tenant** dan tidak ada yang sadar sampai data bocor. Di sini:

- **HTTP tanpa tenant → exception keras** (`app/Exceptions/TenantContextMissingException.php`, render jadi 500 di local / redirect ke `tenants.create` di produksi lewat `withExceptions()` di `bootstrap/app.php`). Gagal berisik jauh lebih baik daripada bocor sunyi.
- **Console/seeder → scope mati.** Ini yang bikin `php artisan db:seed`, factory di test, dan `artisan tinker` tetap jalan tanpa ritual. Aman karena console bukan jalur yang bisa diakses tenant lain.
- **`withoutScope()` eksplisit** untuk kasus HTTP yang memang lintas-tenant (halaman pilih tenant, accept invitation).

Perhatian: `runningInConsole()` juga true saat `php artisan queue:work`. Untuk phase 1 belum ada job yang menyentuh model bertenant; kalau nanti ada, job **wajib** membungkus handle-nya dengan `runFor($this->tenant, ...)`. Catat di §12.

### 2.4 Trait — `app/Models/Concerns/BelongsToTenant.php`

```php
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $context = app(TenantContext::class);

            if (! $context->check()) {
                throw new TenantContextMissingException($model::class);
            }

            $model->setAttribute('tenant_id', $context->id());
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Escape hatch eksplisit, bukan untuk dipakai di controller. */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
```

`creating` hook memaksa isian `tenant_id` otomatis, sehingga tidak ada Action yang perlu mengingat-ingat menuliskannya, **dan** melempar kalau dipanggil di console tanpa `runFor()` (mencegah seeder membuat baris yatim).

**Siapa yang pakai trait ini:** `Account`, `Category`, `Transaction`, `Transfer`, `Subscription`.
**Siapa yang TIDAK:** `User`, `Tenant`, `Plan`, `TenantInvitation`, `TenantUser`.

`TenantInvitation` sengaja dikecualikan: route `/invitations/{token}` hidup di luar tenant context, jadi kalau ia bertrait, halaman accept akan langsung melempar `TenantContextMissingException`. Sebagai gantinya, scoping-nya eksplisit di `TenantInvitationQueryBuilder::forCurrentTenant()` (dipakai halaman daftar anggota) dan `byToken(string)` (dipakai halaman accept, tanpa scope). Ini keputusan sadar, bukan kelalaian — tulis komentar di atas kelas modelnya.

### 2.5 Models — `app/Models/`

Ikuti gaya `app/Models/User.php` yang sudah ada: `@property` docblock lengkap (wajib untuk PHPStan level 7), `#[Fillable([...])]`, `#[UseEloquentBuilder(...)]`, casts di `protected function casts()`.

**`app/Models/Tenant.php`**

```php
#[Fillable(['name', 'subdomain', 'is_custom_subdomain', 'owner_id'])]
#[UseEloquentBuilder(TenantQueryBuilder::class)]
class Tenant extends Model
{
    public function owner(): BelongsTo;                    // User
    public function members(): BelongsToMany;              // User, ->using(TenantUser::class)->withPivot('role','joined_at')
    public function accounts(): HasMany;
    public function categories(): HasMany;
    public function invitations(): HasMany;
    public function subscription(): HasOne;                // ->latestOfMany()

    /** Helper sentral otorisasi — dipakai SEMUA policy. */
    public function hasRole(User $user, TenantRole ...$roles): bool;
    public function roleFor(User $user): ?TenantRole;      // memoized per instance
}
```

`roleFor()` memoize hasil di properti `array<int, ?TenantRole> $roleCache` supaya delapan pemanggilan policy dalam satu request tidak jadi delapan query. Lebih baik lagi: `ResolveTenantMiddleware` sudah tahu role-nya dan menaruhnya di `TenantContext`, jadi `roleFor()` cek context dulu sebelum query.

**`app/Models/TenantUser.php`** — pivot model (`extends Pivot`), casts `role => TenantRole::class`, `joined_at => 'datetime'`. Diperlukan karena kita butuh `TenantMemberPolicy` beroperasi pada baris pivot.

**`app/Models/TenantInvitation.php`**

```php
#[Fillable(['tenant_id','email','role','token','invited_by','status','expires_at'])]
#[UseEloquentBuilder(TenantInvitationQueryBuilder::class)]
class TenantInvitation extends Model
{
    public function tenant(): BelongsTo;
    public function invitedBy(): BelongsTo;

    public function isExpired(): bool;          // expires_at->isPast()
    public function isPending(): bool;          // status === Pending && ! isExpired()
    public function matches(User $user): bool;  // Str::lower($user->email) === Str::lower($this->email)
}
```

**`app/Models/Account.php`**

```php
use BelongsToTenant, HasFactory;

#[Fillable(['name','type','initial_balance','icon','color','is_archived','created_by'])]
#[UseEloquentBuilder(AccountQueryBuilder::class)]
```

Perhatikan: **`balance` sengaja TIDAK fillable.** Satu-satunya cara mengubahnya adalah lewat `AdjustAccountBalance` action (§3). Ini pertahanan struktural, bukan sekadar konvensi.

Relasi: `transactions()`, `transfersOut()` (`from_account_id`), `transfersIn()` (`to_account_id`), `creator()`.

**`app/Models/Category.php`** — `#[Fillable(['name','type','icon','is_default'])]`, relasi `transactions()`.

**`app/Models/Transaction.php`** — `use BelongsToTenant, SoftDeletes;`, `#[Fillable(['account_id','category_id','type','amount','description','transaction_date','created_by'])]`, casts `type => TransactionType::class`, `amount => 'decimal:2'`, `transaction_date => 'date'`.

**`app/Models/Transfer.php`** — sama polanya, relasi `fromAccount()`, `toAccount()`.

**`app/Models/Plan.php`** — casts `features => 'array'`, `price => 'decimal:2'`, helper `feature(string $key, mixed $default = null): mixed`.

**`app/Models/Subscription.php`** — `use BelongsToTenant;`, relasi `plan()`, helper `isActive(): bool`.

Tambahkan juga di `app/Models/User.php`:

```php
#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
// ...
public function tenants(): BelongsToMany;     // ->using(TenantUser::class)->withPivot('role','joined_at')
public function ownedTenants(): HasMany;
public function hasPassword(): bool { return $this->password !== null; }
```

### 2.6 QueryBuilders — `app/QueryBuilders/`

Semua `where` hidup di sini (konvensi 2 `CRUD_FLOW.md`, diadopsi).

```php
// AccountQueryBuilder
public function active(): static;                                  // where is_archived false
public function archived(): static;
public function orderedForListing(): static;                       // is_archived asc, name asc
public function lockedByIds(array $ids): Collection;               // whereIn->orderBy('id')->lockForUpdate()->get()

// CategoryQueryBuilder
public function ofType(CategoryType $type): static;
public function orderedForListing(): static;

// TransactionQueryBuilder
public function ofType(TransactionType $type): static;
public function forAccount(int $accountId): static;
public function forCategory(?int $categoryId): static;
public function betweenDates(CarbonInterface $from, CarbonInterface $to): static;
public function inMonth(CarbonInterface $month): static;
public function ownedBy(User $user): static;                       // created_by = user (policy member)
public function filterFromRequest(array $filters): static;         // whitelist: type, account_id, category_id, from, to
public function latestFirst(): static;
public function sumPerCategory(): Collection;                      // groupBy category_id, selectRaw sum

// TransferQueryBuilder
public function betweenDates(...): static;
public function involvingAccount(int $accountId): static;
public function latestFirst(): static;

// TenantInvitationQueryBuilder
public function byToken(string $token): ?TenantInvitation;         // withoutGlobalScope tidak perlu (model tak bertrait)
public function forTenant(Tenant $tenant): static;
public function pending(): static;                                 // status pending AND expires_at > now
public function expireStale(): int;                                // update status jadi expired (dipanggil dari accept flow)

// TenantQueryBuilder
public function forMember(User $user): static;                     // whereHas members
```

`filterFromRequest(array)` menerima array yang **sudah divalidasi** oleh `TransactionIndexRequest`, bukan `Request` mentah — ini yang menutup lubang injection tanpa perlu lacodix.

---

## 3. Integritas Balance (keputusan penting)

### 3.1 Observer vs Action — rekomendasi: **Action**

**Pakai Action/Service, jangan Observer.** Alasan:

1. **Transfer butuh dua akun terkunci sekaligus dalam satu transaksi DB.** Observer `created` pada `Transfer` berjalan _di dalam_ `Model::save()`, dan untuk mengunci dua baris dengan urutan deterministik kita perlu mengontrol batas transaksi dari luar. Observer memaksa transaksi dibuka di tempat yang tidak terlihat oleh pembaca.
2. **Update transaksi adalah operasi "reverse lama, apply baru" yang butuh nilai lama DAN baru.** Di observer `updating` itu bisa (`getOriginal()`), tapi jadi kode paling berbahaya di aplikasi yang tersembunyi di file yang tak pernah dibuka orang.
3. **Observer diam saat mass update/delete.** `Transaction::query()->where(...)->delete()` tidak memicu observer sama sekali. Untuk data finansial ini adalah bug yang tidak terdeteksi sampai ada yang mengaudit saldo.
4. **Testability.** Action bisa dipanggil langsung di unit test dengan dua akun dan diassert saldonya; observer hanya bisa diuji lewat model.

PRD memang menyebut "balance reversed via observer", tapi itu ditulis sebelum bentuk konkretnya dipikirkan. Rekomendasikan penyimpangan sadar ini ke user (§12).

### 3.2 Struktur Action — `app/Actions/`

```
app/Actions/Account/
    CreateAccount.php          handle(AccountData|array, User): Account       // set balance = initial_balance
    UpdateAccount.php          handle(Account, array): Account                // TIDAK menyentuh balance
    ArchiveAccount.php         handle(Account, bool $archived): Account
    AdjustAccountBalance.php   handle(Account|int $account, string $delta): void   // <- primitif tunggal

app/Actions/Transaction/
    CreateTransaction.php      handle(array $data, User $user): Transaction
    UpdateTransaction.php      handle(Transaction, array $data): Transaction
    DeleteTransaction.php      handle(Transaction): void
    RestoreTransaction.php     handle(Transaction): void

app/Actions/Transfer/
    CreateTransfer.php         handle(array $data, User $user): Transfer
    UpdateTransfer.php         handle(Transfer, array $data): Transfer
    DeleteTransfer.php         handle(Transfer): void

app/Actions/Category/          CreateCategory, UpdateCategory, DeleteCategory
app/Actions/Tenant/            CreateTenant, SeedDefaultCategories, SwitchTenant,
                               InviteMember, ResendInvitation, RevokeInvitation,
                               AcceptInvitation, UpdateMemberRole, RemoveMember
app/Actions/Auth/              CompleteRegistration, FindOrCreateGoogleUser
app/Actions/Billing/           ToggleSubscription
```

Semua plain class, method `handle()`, tanpa trait `AsAction`. Di-resolve lewat method injection di controller (`public function store(StoreTransactionRequest $request, CreateTransaction $action)`).

### 3.3 Primitif: `AdjustAccountBalance`

```php
final class AdjustAccountBalance
{
    /**
     * Terapkan delta ke saldo akun. Harus dipanggil DI DALAM DB::transaction()
     * yang sudah mengunci baris akun terkait.
     */
    public function handle(int $accountId, string $delta): void
    {
        Account::query()
            ->withoutTenantScope()                  // id sudah divalidasi pemanggil
            ->whereKey($accountId)
            ->update(['balance' => DB::raw("balance + ({$delta})")]);
    }
}
```

Gunakan ekspresi SQL (`balance + ?`), bukan `$account->balance + $delta` di PHP — itu yang membuat dua request bersamaan tidak saling menimpa, dan menghindari float. `$delta` selalu string numerik hasil `bcadd`/`bcmul` dari `TransactionType::signum()`.

### 3.4 Strategi locking & transaksi

Aturan tunggal, ditulis di docblock tiap Action yang menyentuh uang:

```php
public function handle(array $data, User $user): Transaction
{
    return DB::transaction(function () use ($data, $user): Transaction {
        // 1. Kunci SEMUA akun yang terlibat, URUT NAIK berdasarkan id.
        $accounts = Account::query()
            ->lockedByIds([$data['account_id']]);   // orderBy('id')->lockForUpdate()

        // 2. Tulis baris domain.
        $transaction = Transaction::create([...]);

        // 3. Terapkan delta.
        $this->adjust->handle($transaction->account_id, $transaction->signedAmount());

        return $transaction;
    });
}
```

**Urutan id menaik saat mengunci adalah aturan anti-deadlock.** Kalau transfer A→B dan B→A berjalan bersamaan dan masing-masing mengunci sesuai urutan logisnya, MySQL akan deadlock. Mengunci selalu `ORDER BY id ASC` menghilangkan kelas bug itu sepenuhnya. Catat: `lockForUpdate()` adalah no-op di SQLite (driver test), jadi test konkurensi nyata hanya berarti di MySQL — lihat §12.

### 3.5 Matriks siklus hidup → efek saldo

| Operasi                       | Yang terjadi dalam satu `DB::transaction`                                                                                                                                          |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `CreateTransaction` (income)  | lock akun; insert; `balance += amount`                                                                                                                                             |
| `CreateTransaction` (expense) | lock akun; insert; `balance -= amount`                                                                                                                                             |
| `UpdateTransaction`           | lock akun **lama dan baru** (urut id); `reverse(old)`; update baris; `apply(new)`. Ini menangani perubahan amount, type, **dan pindah akun** dalam satu jalur, tanpa cabang khusus |
| `DeleteTransaction` (soft)    | lock akun; `reverse(old)`; `$transaction->delete()`                                                                                                                                |
| `RestoreTransaction`          | lock akun; `$transaction->restore()`; `apply(current)`                                                                                                                             |
| Force delete                  | **Tidak diekspos di UI.** Kalau nanti perlu, ia TIDAK mengubah saldo (baris sudah ter-reverse saat soft delete)                                                                    |
| `CreateTransfer`              | lock kedua akun urut id; insert; `from -= amount`; `to += amount`                                                                                                                  |
| `UpdateTransfer`              | lock union(akun lama, akun baru) urut id; reverse pasangan lama; update; apply pasangan baru                                                                                       |
| `DeleteTransfer` (soft)       | lock kedua akun; reverse; delete                                                                                                                                                   |
| `ArchiveAccount`              | tidak menyentuh saldo. Akun terarsip tidak boleh dipilih di form transaksi baru (validasi di FormRequest lewat `Rule::exists()->where('is_archived', false)`)                      |
| `UpdateAccount`               | `initial_balance` **tidak boleh diubah setelah create** (kalau boleh, ia harus jadi delta ke `balance`). Untuk prototype: kunci field-nya di form edit. Catat di §12               |

Helper di model: `Transaction::signedAmount(): string` = `bcmul($this->amount, (string) $this->type->signum(), 2)`.

**Invariant yang diuji:** untuk setiap akun, `balance == initial_balance + Σ signedAmount(transaksi hidup) + Σ transfer masuk − Σ transfer keluar`. Ini jadi satu helper test `assertAccountBalanceReconciles(Account $account)` di `tests/Pest.php` dan dipanggil di akhir setiap test uang.

---

## 4. Resolusi Tenant Aktif (session-based)

### 4.1 Middleware — `app/Http/Middleware/ResolveTenant.php`

Alias `'tenant'` didaftarkan di `bootstrap/app.php`:

```php
$middleware->alias(['tenant' => ResolveTenant::class]);
```

Logika:

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();                    // selalu setelah 'auth'

    $tenantId = $request->session()->get(self::SESSION_KEY);   // 'current_tenant_id'

    $membership = $user->tenants()                             // BelongsToMany, tanpa global scope
        ->when($tenantId, fn ($q) => $q->where('tenants.id', $tenantId))
        ->orderBy('tenants.name')
        ->first();

    if ($membership === null) {
        // Kasus: user baru tanpa tenant, ATAU user dikeluarkan dari tenant
        // yang masih tersimpan di session.
        $request->session()->forget(self::SESSION_KEY);

        $fallback = $user->tenants()->orderBy('tenants.name')->first();

        if ($fallback === null) {
            return to_route('tenants.create');
        }

        $membership = $fallback;
    }

    $role = TenantRole::from($membership->pivot->role);

    app(TenantContext::class)->set($membership, $role);
    $request->session()->put(self::SESSION_KEY, $membership->id);

    return $next($request);
}
```

Tiga hal yang ditangani sekaligus di sini:

- **Session menunjuk tenant yang user-nya sudah di-kick** → jatuh ke tenant lain, bukan 403 misterius.
- **User belum punya tenant** → diarahkan ke `tenants.create`, bukan exception.
- **Session kosong (login pertama)** → ambil tenant pertama otomatis.

`SESSION_KEY = 'fluxa.current_tenant_id'`. Session driver `database` sudah aktif di `.env.example` dan tabel `sessions` sudah ada di migration default — tidak ada pekerjaan tambahan.

### 4.2 Peta route vs middleware

```php
// routes/web.php

Route::inertia('/', 'Welcome')->name('home');

// --- Di luar tenant context ---
Route::middleware(['auth'])->group(function () {
    Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('tenants',       [TenantController::class, 'store'])->name('tenants.store');
    Route::post('tenants/{tenant}/switch', TenantSwitchController::class)->name('tenants.switch');
});

// Publik / semi-publik — token yang jadi otoritas, bukan session tenant.
Route::get('invitations/{token}',        [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}/accept',[InvitationAcceptanceController::class, 'store'])
    ->middleware('auth')->name('invitations.accept');

// Google OAuth
Route::get('auth/google/redirect', GoogleRedirectController::class)->name('auth.google.redirect');
Route::get('auth/google/callback', GoogleCallbackController::class)->name('auth.google.callback');

// --- Di dalam tenant context ---
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('accounts', AccountController::class)->except('show');
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('transactions', TransactionController::class)->except('show');
    Route::resource('transfers', TransferController::class)->except('show');

    Route::get('members',  [MemberController::class, 'index'])->name('members.index');
    Route::patch('members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('members/{user}',[MemberController::class, 'destroy'])->name('members.destroy');

    Route::resource('invitations', InvitationController::class)->only(['store', 'destroy']);
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'update'])->name('invitations.resend');

    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/toggle', [BillingController::class, 'store'])->name('billing.toggle');

    Route::get('settings/tenant',   [TenantController::class, 'edit'])->name('tenants.edit');
    Route::patch('settings/tenant', [TenantController::class, 'update'])->name('tenants.update');
});

require __DIR__.'/settings.php';
```

`routes/settings.php` (profil user) **tidak** diberi middleware `tenant` — user bisa mengurus profilnya tanpa tenant aktif.

`TenantSwitchController` sengaja **di luar** grup `tenant`: kalau middleware tenant sudah menolak karena session rusak, switcher masih harus bisa dipakai untuk memperbaiki keadaan.

**Route model binding & tenant scope.** Karena global scope aktif di dalam grup `tenant`, `{account}`/`{transaction}` otomatis ter-scope: akun milik tenant lain langsung 404 sebelum controller dijalankan. Itu lapisan pertama isolasi; Policy adalah lapisan kedua. Pengecualian: `{tenant}` di `tenants.switch` dan `{user}` di `members.*` tidak ter-scope — keduanya divalidasi eksplisit di controller/policy.

### 4.3 Shared props — `app/Http/Middleware/HandleInertiaRequests.php`

Tambahkan ke `share()`:

```php
'tenant' => fn () => $this->tenantProps($request),
```

di mana `tenantProps()` mengembalikan `null` kalau tak ada tenant aktif, atau:

```php
[
    'current' => ['id' => ..., 'name' => ..., 'subdomain' => ...],
    'role'    => 'owner',
    'memberships' => [ ['id' =>, 'name' =>, 'role' =>], ... ],   // untuk switcher
    'abilities' => [                                             // untuk sembunyikan menu
        'manage_accounts'  => bool,
        'manage_categories'=> bool,
        'invite_members'   => bool,
        'view_billing'     => bool,
        'manage_tenant'    => bool,
    ],
]
```

Gunakan closure (lazy) supaya prop ini tidak dievaluasi di halaman auth. `memberships` diambil dari `$user->tenants` yang sudah di-load middleware — tidak ada query tambahan.

---

## 5. Fase 3 — Policies

Daftarkan lewat atribut `#[UsePolicy(AccountPolicy::class)]` di masing-masing model (native Laravel 13) — tidak perlu `AuthServiceProvider`.

Semua policy memakai satu helper:

```php
private function role(User $user): ?TenantRole
{
    return app(TenantContext::class)->role();     // sudah di-set middleware, 0 query
}
```

dan untuk kasus di luar konteks middleware, fallback ke `$tenant->hasRole($user, ...)`.

### 5.1 Matriks → implementasi

| Aksi (PRD)              | Owner |       Admin       |    Member     | Policy method                                                                                              |
| ----------------------- | :---: | :---------------: | :-----------: | ---------------------------------------------------------------------------------------------------------- |
| Lihat semua data tenant |   ✔   |         ✔         |       ✔       | `viewAny`, `view` di semua policy → `true` (tenant scope sudah membatasi)                                  |
| Buat account            |   ✔   |         ✔         |       ✔       | `AccountPolicy::create`                                                                                    |
| Edit/hapus account      |   ✔   |         ✔         |       ✘       | `AccountPolicy::update`/`delete` → `role in [owner, admin]`                                                |
| Buat transaksi          |   ✔   |         ✔         |       ✔       | `TransactionPolicy::create`                                                                                |
| Edit/hapus transaksi    |   ✔   |         ✔         | milik sendiri | `TransactionPolicy::update`/`delete` → owner/admin true; member → `$transaction->created_by === $user->id` |
| CRUD kategori           |   ✔   |         ✔         |       ✘       | `CategoryPolicy::create/update/delete`                                                                     |
| Invite anggota          |   ✔   |         ✔         |       ✘       | `TenantInvitationPolicy::create/delete/resend`                                                             |
| Ubah role anggota       |   ✔   |         ✘         |       ✘       | `TenantMemberPolicy::updateRole`                                                                           |
| Kick anggota            |   ✔   | ✔ (kecuali owner) |       ✘       | `TenantMemberPolicy::remove`                                                                               |
| Ubah pengaturan tenant  |   ✔   |         ✘         |       ✘       | `TenantPolicy::update`                                                                                     |
| Lihat billing           |   ✔   |         ✘         |       ✘       | `SubscriptionPolicy::viewAny/update`                                                                       |
| Hapus tenant            |   ✔   |         ✘         |       ✘       | `TenantPolicy::delete`                                                                                     |

Transfer mengikuti aturan transaksi (`TransferPolicy` ≡ `TransactionPolicy`).

### 5.2 Guard yang tidak boleh lupa

```php
// TenantMemberPolicy
public function remove(User $actor, Tenant $tenant, User $target): Response
{
    // Owner tidak bisa dikeluarkan siapapun, termasuk dirinya sendiri.
    if ($tenant->roleFor($target) === TenantRole::Owner) {
        return Response::deny('Owner tidak bisa dikeluarkan dari tenant.');
    }

    return $tenant->hasRole($actor, TenantRole::Owner, TenantRole::Admin)
        ? Response::allow()
        : Response::deny();
}

public function updateRole(User $actor, Tenant $tenant, User $target): Response
{
    // Hanya owner; dan role 'owner' tidak bisa diberikan lewat sini
    // (butuh transfer ownership eksplisit — di luar scope prototype).
}
```

Aturan "minimal selalu ada 1 owner aktif" dijaga dari dua sisi: policy di atas + `RemoveMember` action yang menolak kalau target adalah `$tenant->owner_id`.

### 5.3 `denyAsNotFound()` untuk kebocoran informasi

Untuk resource bertenant, global scope sudah membuat baris tenant lain 404 di route binding. Tapi untuk `members.*` dan `tenants.switch` (yang tidak ter-scope), gunakan:

```php
return Response::denyAsNotFound();
```

supaya 403 tidak mengkonfirmasi bahwa tenant/user dengan id itu ada.

### 5.4 `can_edit` / `can_delete` per item

Dihitung di JsonResource, satu tempat:

```php
// app/Http/Resources/TransactionResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'type' => $this->type->value,
        'amount' => (string) $this->amount,          // mentah, diformat di frontend
        'description' => $this->description,
        'transaction_date' => $this->transaction_date->toDateString(),
        'account' => AccountResource::make($this->whenLoaded('account')),
        'category' => CategoryResource::make($this->whenLoaded('category')),
        'created_by' => $this->whenLoaded('creator', fn () => ['id' => ..., 'name' => ...]),
        'can_edit' => $request->user()->can('update', $this->resource),
        'can_delete' => $request->user()->can('delete', $this->resource),
    ];
}
```

Perhatian N+1: `TransactionPolicy` membaca `$transaction->created_by` (kolom, bukan relasi) dan role dari `TenantContext` (in-memory) — jadi 50 item = 0 query tambahan. Itu sebabnya role disimpan di context, bukan diambil ulang per policy call.

Resources lain: `AccountResource`, `CategoryResource`, `TransferResource`, `TenantMemberResource`, `TenantInvitationResource`, `SubscriptionResource`, `PlanResource`.

---

## 6. Fase 4 — Autentikasi (Socialite + Fortify berdampingan)

### 6.1 Instalasi

```bash
composer require laravel/socialite
```

`config/services.php` tambah:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
],
```

`.env.example` tambah `GOOGLE_CLIENT_ID=`, `GOOGLE_CLIENT_SECRET=`, `GOOGLE_REDIRECT_URI=`, plus `APP_TIMEZONE=Asia/Jakarta` dan `APP_LOCALE=id` (untuk format tanggal di email).

### 6.2 Bagaimana Socialite hidup berdampingan dengan Fortify

Fortify sudah menangani login/register/2FA/passkey dan **tidak perlu disentuh** — Socialite hanya menambah pintu masuk kedua yang berakhir di `Auth::login($user)` yang sama.

Dua penyesuaian wajib:

**(a) Password NULL memecahkan login Fortify.** Setelah `password` jadi nullable, pipeline auth default Fortify memanggil `Hash::check($request->password, $user->password)` dengan argumen `null` → deprecation PHP 8.3 dan perilaku tak terdefinisi. Tambahkan di `FortifyServiceProvider::configureActions()`:

```php
Fortify::authenticateUsing(function (Request $request): ?User {
    $user = User::query()->where('email', $request->email)->first();

    if ($user === null || ! $user->hasPassword()) {
        return null;                       // akun Google-only: jalur password ditutup
    }

    return Hash::check($request->password, $user->password) ? $user : null;
});
```

**(b) `RequirePassword` dan `current_password` memblokir user Google-only.** `routes/settings.php` memasang `RequirePassword::class` pada `settings/security`, dan `app/Http/Requests/Settings/ProfileDeleteRequest.php` mewajibkan `current_password`. Untuk user tanpa password keduanya buntu. Mitigasi minimal prototype: di `SecurityController::edit` dan form hapus akun, tampilkan blok "Atur Password" dulu bagi user Google-only (`auth.user.has_password === false`, prop baru dari `HandleInertiaRequests`). Ini dicatat sebagai hutang kecil di §12.

### 6.3 Controller

`app/Http/Controllers/Auth/GoogleRedirectController.php` (invokable):

```php
public function __invoke(Request $request): RedirectResponse
{
    // Simpan token undangan agar callback tahu harus ke mana.
    if ($token = $request->query('invitation')) {
        $request->session()->put('fluxa.invitation_token', $token);
    }

    return Socialite::driver('google')->redirect();
}
```

`app/Http/Controllers/Auth/GoogleCallbackController.php`:

```php
public function __invoke(
    Request $request,
    FindOrCreateGoogleUser $findOrCreate,
    CompleteRegistration $complete,
): RedirectResponse {
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (Throwable) {
        return to_route('login')->withErrors(['email' => 'Gagal login dengan Google.']);
    }

    [$user, $wasCreated] = $findOrCreate->handle($googleUser);

    Auth::login($user, remember: true);

    if ($wasCreated) {
        $complete->handle($user);           // buat tenant, KECUALI kalau sedang accept invite
    }

    if ($token = $request->session()->pull('fluxa.invitation_token')) {
        return to_route('invitations.show', $token);
    }

    return to_route('dashboard');
}
```

`FindOrCreateGoogleUser::handle()` urutan pencarian:

1. `User::where('google_id', $googleUser->getId())` → pakai, update `avatar`.
2. `User::where('email', $googleUser->getEmail())` → **link**: isi `google_id`, `avatar`, set `email_verified_at` kalau masih null. Ini yang membuat akun password lama bisa dipakai lewat Google.
3. Belum ada → `User::create(['name', 'email', 'google_id', 'avatar', 'password' => null, 'email_verified_at' => now()])`.

`CompleteRegistration::handle(User $user)`:

```php
// Jangan buat tenant sendiri kalau user ini datang lewat undangan.
if (session()->has('fluxa.invitation_token')) {
    return;
}

app(CreateTenant::class)->handle($user, name: "Keuangan {$user->name}");
```

Untuk jalur email/password, panggil `CompleteRegistration` dari `app/Actions/Fortify/CreateNewUser.php` tepat setelah `User::create(...)`. Satu tempat aturan "registrasi pertama membuat tenant", dipakai dua jalur.

### 6.4 `CreateTenant` action

```php
public function handle(User $user, string $name, ?string $subdomain = null): Tenant
{
    return DB::transaction(function () use ($user, $name, $subdomain): Tenant {
        $tenant = Tenant::create([
            'name' => $name,
            'subdomain' => $subdomain ?? $this->generateSubdomain($name),
            'owner_id' => $user->id,
        ]);

        $tenant->members()->attach($user->id, [
            'role' => TenantRole::Owner->value,
            'joined_at' => now(),
        ]);

        // Global scope belum aktif di titik ini -> jalankan dalam konteks tenant baru.
        app(TenantContext::class)->runFor($tenant, function () use ($tenant, $user): void {
            app(SeedDefaultCategories::class)->handle($tenant);
            app(CreateDefaultAccount::class)->handle($tenant, $user);   // opsional: 1 kantong "Tunai"
            app(StartFreeSubscription::class)->handle($tenant);
        });

        session()->put(ResolveTenant::SESSION_KEY, $tenant->id);

        return $tenant;
    });
}
```

`generateSubdomain()`: `Str::slug($name)` dipotong 24 karakter + `-` + 4 karakter acak, dicek terhadap daftar reserved (`www`, `api`, `admin`, `app`, `mail`, `billing`) dan unique. Tanpa `atrox/haikunator` untuk prototype — satu package lebih sedikit, dan nilainya cuma placeholder. Taruh reserved list di `config/fluxa.php` supaya nanti dipakai ulang oleh validasi custom subdomain.

---

## 7. Fase 4 — Flow Undangan Anggota

### 7.1 `InviteMember` action

```php
public function handle(Tenant $tenant, User $inviter, string $email, TenantRole $role): TenantInvitation
{
    // Guard 1: sudah jadi member?
    // Guard 2: role yang diundang tidak boleh Owner.
    // Guard 3: (hook feature gating) $plan->feature('max_members')

    return TenantInvitation::updateOrCreate(
        ['tenant_id' => $tenant->id, 'email' => Str::lower($email)],
        [
            'role' => $role->value,
            'token' => Str::random(48),           // token BARU -> token lama otomatis mati
            'invited_by' => $inviter->id,
            'status' => InvitationStatus::Pending->value,
            'expires_at' => now()->addDays(7),
        ],
    );
}
```

`updateOrCreate` pada `(tenant_id, email)` memenuhi tiga requirement sekaligus: re-invite tidak menghasilkan duplikat, resend menerbitkan token baru yang meng-invalidate lama, dan undangan expired bisa "dihidupkan" dengan resend. `ResendInvitation` cukup memanggil `InviteMember` lagi.

`RevokeInvitation`: `$invitation->update(['status' => Revoked, 'token' => Str::random(48)])` — token diacak ulang supaya link yang sudah dikirim benar-benar mati, bukan cuma ditandai.

Notifikasi: `app/Notifications/TenantInvitationNotification.php` (mailable via Notification), body Bahasa Indonesia, link `route('invitations.show', $invitation->token)`. `MAIL_MAILER=log` di `.env.example` jadi prototype tidak butuh SMTP; link-nya tetap ditampilkan di halaman daftar undangan dengan tombol "Salin link" — ini yang bikin fitur bisa dites tanpa email sungguhan.

### 7.2 `InvitationAcceptanceController`

`show(string $token)` — **tanpa middleware `tenant`, tanpa `auth`**:

```php
$invitation = TenantInvitation::query()->byToken($token);

// 404 kalau token tidak ada — jangan bedakan "tidak ada" vs "sudah dipakai".
abort_if($invitation === null, 404);

// Kedaluwarsa -> tandai + tampilkan state khusus (bukan 404, supaya user tahu harus minta kirim ulang).
if ($invitation->status === Pending && $invitation->isExpired()) {
    $invitation->update(['status' => InvitationStatus::Expired->value]);
}

return Inertia::render('invitations/Show', [
    'invitation' => [
        'tenant_name' => $invitation->tenant->name,
        'email' => $invitation->email,
        'role' => $invitation->role->value,
        'status' => $invitation->status->value,     // pending|accepted|expired|revoked
        'inviter_name' => $invitation->invitedBy->name,
    ],
    'token' => $token,
    'state' => $this->resolveState($invitation, $request->user()),
    'google_url' => route('auth.google.redirect', ['invitation' => $token]),
]);
```

`resolveState()` mengembalikan salah satu string yang dikonsumsi halaman Vue — inilah seluruh percabangan PRD dalam satu tempat yang bisa dites:

| `state`                            | Kondisi                                             | Yang ditampilkan                                                                                                               |
| ---------------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `guest`                            | belum login                                         | Tombol "Lanjut dengan Google" + link login email/password (keduanya membawa `?invitation={token}`)                             |
| `ready`                            | login, `invitation->matches($user)`, status pending | Tombol "Terima Undangan" (POST)                                                                                                |
| `email_mismatch`                   | login, email berbeda                                | Pesan "Undangan ini untuk `budi@…`, Anda login sebagai `siti@…`" + tombol logout-dan-login-ulang. **Tidak ada tombol terima.** |
| `already_member`                   | sudah ada di `tenant_user`                          | Tombol "Buka Tenant" (switch + redirect dashboard)                                                                             |
| `expired` / `revoked` / `accepted` | status                                              | Pesan + instruksi minta kirim ulang                                                                                            |

`store()` (POST, `middleware('auth')`) — **validasi ulang semuanya di server, jangan percaya `state` dari client**:

```php
public function store(Request $request, string $token, AcceptInvitation $action): RedirectResponse
{
    $invitation = TenantInvitation::query()->byToken($token);

    abort_if($invitation === null, 404);
    abort_unless($invitation->isPending(), 410);                 // expired/revoked/accepted
    abort_unless($invitation->matches($request->user()), 403);   // <- prinsip keamanan kunci PRD

    $tenant = $action->handle($invitation, $request->user());

    $request->session()->put(ResolveTenant::SESSION_KEY, $tenant->id);
    Inertia::flash('toast', ['type' => 'success', 'message' => "Bergabung ke {$tenant->name}."]);

    return to_route('dashboard');
}
```

`AcceptInvitation::handle()` dalam `DB::transaction`: `attach` ke `tenant_user` (pakai `syncWithoutDetaching` agar idempotent kalau tombol diklik dua kali), lalu `$invitation->update(['status' => Accepted])`.

**Perbandingan email case-insensitive** (`Str::lower()` di kedua sisi) — Google mengembalikan email persis, tapi user bisa register dengan kapitalisasi berbeda.

**User baru lewat undangan.** Jalur: `/invitations/{token}` (state `guest`) → klik Google → `GoogleRedirectController` menaruh token di session → callback membuat user → `CompleteRegistration` melihat token di session dan **tidak** membuat tenant pribadi → redirect balik ke `invitations.show` → state `ready` → terima. Kalau email Google ternyata beda, state jadi `email_mismatch` dan user tetap punya akun (tanpa tenant) — dia diarahkan ke `tenants.create`. Itu perilaku yang benar, bukan bug.

---

## 8. Fase 4 — Controllers

Semua controller hanya berisi method REST (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) atau invokable. Tidak ada `where` di controller — semua lewat QueryBuilder. Otorisasi lewat `Gate::authorize()`, bukan trait.

| Controller                                              | Method                                      | Catatan                                                                                                                                                                                                                                                                                                                                                             |
| ------------------------------------------------------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `DashboardController`                                   | `__invoke`                                  | §9.1                                                                                                                                                                                                                                                                                                                                                                |
| `AccountController`                                     | index, create, store, edit, update, destroy | `destroy` = soft-guard: kalau akun punya transaksi → 422 dengan pesan "arsipkan saja"; kalau kosong → hard delete. Plus `archive()` (patch) — ini menyimpang dari REST murni, alternatif: `Route::resource('accounts.archive', ...)->only('store')`. **Rekomendasi: controller terpisah `ArchiveAccountController::__invoke`** agar `AccountController` tetap murni |
| `CategoryController`                                    | index, create, store, edit, update, destroy | `destroy` menolak kalau `is_default` masih dipakai transaksi → set `category_id` null via `nullOnDelete`, atau tolak dengan 422. **Rekomendasi: tolak 422** (audit finansial)                                                                                                                                                                                       |
| `TransactionController`                                 | index, create, store, edit, update, destroy | `index` menerima `TransactionIndexRequest` (validasi filter: `type`, `account_id`, `category_id`, `from`, `to`, `page`) lalu `->filterFromRequest($request->validated())`                                                                                                                                                                                           |
| `TransferController`                                    | index, create, store, edit, update, destroy | `store` validasi `from_account_id != to_account_id` (rule `different`) dan keduanya `is_archived = false`                                                                                                                                                                                                                                                           |
| `TenantController`                                      | create, store, edit, update, destroy        | `create`/`store` di luar tenant context                                                                                                                                                                                                                                                                                                                             |
| `TenantSwitchController`                                | `__invoke`                                  | Validasi membership → set session → `back()`                                                                                                                                                                                                                                                                                                                        |
| `MemberController`                                      | index, update, destroy                      | `index` juga mengirim daftar `invitations` (satu halaman "Anggota")                                                                                                                                                                                                                                                                                                 |
| `InvitationController`                                  | store, destroy                              | + `ResendInvitationController::__invoke`                                                                                                                                                                                                                                                                                                                            |
| `InvitationAcceptanceController`                        | show, store                                 | §7.2                                                                                                                                                                                                                                                                                                                                                                |
| `BillingController`                                     | index, store                                | `store` = mock toggle                                                                                                                                                                                                                                                                                                                                               |
| `GoogleRedirectController` / `GoogleCallbackController` | `__invoke`                                  | §6.3                                                                                                                                                                                                                                                                                                                                                                |

FormRequests di `app/Http/Requests/` (ikuti struktur folder yang sudah ada, `Settings/` jadi preseden):

```
app/Http/Requests/Account/{StoreAccountRequest,UpdateAccountRequest}.php
app/Http/Requests/Category/{StoreCategoryRequest,UpdateCategoryRequest}.php
app/Http/Requests/Transaction/{StoreTransactionRequest,UpdateTransactionRequest,TransactionIndexRequest}.php
app/Http/Requests/Transfer/{StoreTransferRequest,UpdateTransferRequest}.php
app/Http/Requests/Tenant/{StoreTenantRequest,UpdateTenantRequest,InviteMemberRequest,UpdateMemberRoleRequest}.php
```

Contoh aturan yang menjaga isolasi tenant di level validasi (lapisan ketiga):

```php
// StoreTransactionRequest
'account_id' => ['required', Rule::exists('accounts', 'id')
    ->where('tenant_id', app(TenantContext::class)->id())
    ->where('is_archived', false)],
'category_id' => ['nullable', Rule::exists('categories', 'id')
    ->where('tenant_id', app(TenantContext::class)->id())],
'type' => ['required', Rule::enum(TransactionType::class)],
'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
'transaction_date' => ['required', 'date', 'before_or_equal:today'],
'description' => ['nullable', 'string', 'max:500'],
```

`amount` divalidasi `numeric`; frontend mengirim angka mentah (bukan "Rp 1.500.000") — komponen input Rupiah menyimpan nilai mentah di `form.amount` dan hanya menampilkan versi terformat.

Semua `authorize()` di FormRequest **return `true`** — otorisasi ada di Policy lewat `Gate::authorize()` di controller, satu tempat.

---

## 9. Fase 5 — Halaman Inertia / Vue

### 9.1 Dashboard

`DashboardController::__invoke`:

```php
$now = CarbonImmutable::now();

return Inertia::render('Dashboard', [
    'summary' => [
        'total_balance' => Account::query()->active()->sum('balance'),
        'income_this_month'  => Transaction::query()->ofType(Income)->inMonth($now)->sum('amount'),
        'expense_this_month' => Transaction::query()->ofType(Expense)->inMonth($now)->sum('amount'),
        'period_label' => $now->translatedFormat('F Y'),
    ],
    'accounts' => AccountResource::collection(Account::query()->active()->orderedForListing()->get()),
    'expense_by_category' => ExpenseByCategoryResource::collection(
        Transaction::query()->ofType(Expense)->inMonth($now)->sumPerCategory()
    ),
    'recent_transactions' => TransactionResource::collection(
        Transaction::query()->with(['account', 'category'])->latestFirst()->limit(8)->get()
    ),
]);
```

`sumPerCategory()` di `TransactionQueryBuilder` melakukan `select('category_id', DB::raw('SUM(amount) as total'))->groupBy('category_id')->with('category')` — satu query, bukan loop.

### 9.2 Struktur halaman

```
resources/js/pages/
├── Dashboard.vue                    (ganti isi placeholder yang sekarang)
├── accounts/     Index.vue  Create.vue  Edit.vue  Form.vue
├── categories/   Index.vue  Create.vue  Edit.vue  Form.vue
├── transactions/ Index.vue  Create.vue  Edit.vue  Form.vue
├── transfers/    Index.vue  Create.vue  Edit.vue  Form.vue
├── members/      Index.vue                          (daftar anggota + undangan + form invite)
├── invitations/  Show.vue                            (halaman accept, layout Auth)
├── tenants/      Create.vue  Settings.vue
└── billing/      Index.vue

resources/js/components/fluxa/
├── TenantSwitcher.vue               (DropdownMenu, dipasang di AppSidebar header)
├── CurrencyInput.vue                (input angka + preview Rupiah)
├── MoneyText.vue                    (<MoneyText :value amount />, warna hijau/merah)
├── AccountCard.vue
├── TransactionListItem.vue          (mobile-first: list item, bukan tabel)
├── TransactionTable.vue             (md+ : tabel)
├── EmptyState.vue
├── PageHeader.vue                   (judul + tombol aksi utama)
└── charts/
    ├── DonutChart.vue
    └── BarChart.vue

resources/js/composables/useCurrency.ts
resources/js/composables/useTenant.ts          (baca page.props.tenant, abilities)
resources/js/types/fluxa.d.ts                  (interface Account, Transaction, dst)
```

Pola `Form.vue` + `Create.vue`/`Edit.vue` persis seperti `CRUD_FLOW.md` §5: `Form.vue` punya semua field + `useForm` + `submit()`, wrapper cuma memutuskan `action` dan `method`. Impor URL dari Wayfinder (`import { store, update } from '@/routes/transactions'`) — jangan tulis path literal.

Komponen UI yang dibutuhkan sudah tersedia di `resources/js/components/ui/`: `button, card, input, label, select, dialog, dropdown-menu, badge, checkbox, separator, sheet, sidebar, skeleton, sonner, tooltip`. **Yang belum ada dan perlu ditambah lewat shadcn-vue CLI:** `table` (untuk desktop), `tabs` (income/expense switch), `textarea` (deskripsi), `popover` + `calendar` (date picker) — atau pakai `<input type="date">` native untuk menghemat (**rekomendasi prototype: native date input**, bagus di mobile).

Mobile-first: daftar transaksi memakai `TransactionListItem` (stack) di bawah `md`, `TransactionTable` di `md:` ke atas. Tombol aksi utama jadi floating action button di mobile. Form pakai satu kolom di mobile, `sm:grid-cols-2` di atasnya.

### 9.3 Chart — rekomendasi: **SVG tulis tangan, tanpa dependency**

Opsi yang dipertimbangkan:

| Opsi                       | Penilaian                                                                                                                                                          |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `chart.js` + `vue-chartjs` | Matang, Vue 3 OK. Tapi +~200KB, styling via JS config (bertabrakan dengan pendekatan Tailwind token), dan warna dark-mode harus di-sync manual dengan CSS variable |
| `@unovis/vue`              | Bagus dan modern, tapi API besar untuk dua chart                                                                                                                   |
| `apexcharts` / `echarts`   | Terlalu berat untuk prototype                                                                                                                                      |
| Recharts                   | **Tidak bisa** — React-only                                                                                                                                        |
| **SVG tulis tangan**       | ✅ 0 dependency, `currentColor` + Tailwind token bikin dark mode gratis, tidak ada masalah SSR (repo punya `build:ssr`), dan chart-nya memang cuma dua             |

**Rekomendasi: SVG tulis tangan** untuk phase 1. Dua komponen:

- `DonutChart.vue` — pengeluaran per kategori bulan ini. Props: `segments: { label, value, color }[]`. Implementasi: lingkaran dengan `stroke-dasharray`/`stroke-dashoffset`, ±50 baris. Legend sebagai list di bawah/samping (mobile: di bawah).
- `BarChart.vue` — income vs expense (phase 1 cukup 2 bar bulan berjalan; struktur props `series: { label, income, expense }[]` sudah menampung "beberapa bulan terakhir" di phase 2 tanpa rewrite).

Palet: ambil dari skill `dataviz` (`references/palette.md`) untuk warna kategorikal yang aman di light+dark, dan definisikan sebagai CSS variable di `resources/css/app.css` supaya konsisten dengan token Tailwind 4 yang sudah ada. Setiap segment wajib punya label + nilai terformat di legend — jangan andalkan warna saja (aksesibilitas), dan tanpa library berarti tidak ada tooltip hover gratis.

Kalau nanti dashboard analytics phase 2 (cash flow multi-bulan, trend saldo per akun) masuk, itu titik yang tepat untuk pindah ke `chart.js`. Jaga API props `DonutChart`/`BarChart` tetap sederhana supaya penggantinya drop-in.

### 9.4 Format Rupiah

**Backend:** kirim **angka mentah** sebagai string decimal (`"1500000.00"`), bukan string terformat. Alasan: frontend butuh angka untuk chart, sorting, dan input; string "Rp 1.500.000" harus di-parse balik. `AccountResource`/`TransactionResource` mengirim `'amount' => (string) $this->amount`.

Formatter backend tetap diperlukan untuk **email dan notifikasi** (tidak lewat Vue). Taruh di `app/Support/Rupiah.php`:

```php
final class Rupiah
{
    public static function format(string|float|int $amount, bool $withDecimals = false): string;
}
```

dan daftarkan Blade directive `@rupiah($value)` di `AppServiceProvider::boot()` untuk template mail.

**Frontend:** `resources/js/lib/currency.ts`

```ts
const idr = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

export function formatRupiah(value: number | string): string; // "Rp 1.500.000"
export function formatRupiahCompact(value: number | string): string; // "Rp 1,5 jt" untuk kartu ringkas
export function parseRupiah(input: string): number; // untuk CurrencyInput
```

plus composable tipis `useCurrency()` yang mengekspos ketiganya (supaya bisa dipakai di template tanpa import per file).

`Intl.NumberFormat` dengan `'id-ID'` menghasilkan `Rp 1.500.000` (spasi non-breaking setelah Rp) — pastikan test snapshot tidak patah karena itu. `maximumFractionDigits: 0` karena Rupiah praktis tidak pakai sen; nilai desimal di DB tetap disimpan untuk kalau-kalau.

`MoneyText.vue` menerima `:value` dan `:variant="'income' | 'expense' | 'neutral'"` lalu memberi warna + tanda `+`/`−`. Satu komponen = satu tempat aturan warna uang.

---

## 10. Seeding

### 10.1 `database/seeders/PlanSeeder.php`

Idempotent (`updateOrCreate` by `slug`), dipanggil dari `DatabaseSeeder` dan aman di produksi:

```php
['slug' => 'free', 'name' => 'Free', 'price' => 0, 'billing_period' => 'monthly',
 'features' => ['max_members' => 3, 'max_accounts' => 3, 'custom_subdomain' => false, 'export' => false]]

['slug' => 'pro-monthly', 'name' => 'Pro', 'price' => 35000, 'billing_period' => 'monthly',
 'features' => ['max_members' => null, 'max_accounts' => null, 'custom_subdomain' => true, 'export' => true]]
```

(`null` = unlimited.)

### 10.2 Kategori default per tenant — **Action, bukan Seeder**

`app/Actions/Tenant/SeedDefaultCategories.php`. Harus Action karena dipanggil setiap kali tenant baru dibuat di runtime, bukan sekali saat deploy. Sumber datanya konstanta di `config/fluxa.php`:

```php
'default_categories' => [
    ['name' => 'Gaji',      'type' => 'income',  'icon' => 'banknote'],
    ['name' => 'Lainnya',   'type' => 'income',  'icon' => 'circle-plus'],
    ['name' => 'Makan',     'type' => 'expense', 'icon' => 'utensils'],
    ['name' => 'Transport', 'type' => 'expense', 'icon' => 'car'],
    ['name' => 'Belanja',   'type' => 'expense', 'icon' => 'shopping-bag'],
    ['name' => 'Tagihan',   'type' => 'expense', 'icon' => 'receipt'],
    ['name' => 'Lainnya',   'type' => 'expense', 'icon' => 'ellipsis'],
],
```

Semua `is_default = true`. Catat: "Lainnya" muncul dua kali dengan `type` berbeda — unique constraint `(tenant_id, type, name)` mengakomodasi ini, `(tenant_id, name)` tidak. Itu sebabnya `type` masuk ke unique key di §1.6.

`is_default` **tidak** mengunci edit/hapus (PRD: "tenant bebas edit/hapus"); ia cuma penanda asal-usul untuk UI.

### 10.3 `database/seeders/DemoSeeder.php`

Dipanggil manual (`php artisan db:seed --class=DemoSeeder`), bukan dari `DatabaseSeeder`. Isi:

- 3 user: `owner@fluxa.test`, `admin@fluxa.test`, `member@fluxa.test` (password `password`).
- Tenant "Keluarga Demo" dengan ketiganya di role masing-masing + tenant kedua "Komunitas RT 05" yang hanya berisi owner — **ini yang membuat tenant switcher punya sesuatu untuk di-switch dan membuat test isolasi punya data pembanding.**
- 4 kantong: Tunai, Bank BCA, GoPay, Dompet Darurat.
- ±60 transaksi tersebar 3 bulan terakhir + 5 transfer, **semua dibuat lewat Action**, bukan `Transaction::factory()->create()` langsung — supaya saldo hasil seeding konsisten dan seeder sekaligus jadi smoke test jalur uang.
- 1 undangan pending ke `calon@fluxa.test`.

Seluruh body seeder dibungkus `app(TenantContext::class)->runFor($tenant, fn () => ...)`, karena `TenantScope` mati di console tapi hook `creating` di trait tetap butuh `tenant_id`.

`DatabaseSeeder` diubah jadi memanggil `PlanSeeder` saja (plus user test yang sudah ada).

### 10.4 Factories

`database/factories/{TenantFactory,AccountFactory,CategoryFactory,TransactionFactory,TransferFactory,PlanFactory,SubscriptionFactory,TenantInvitationFactory}.php`.

Peringatan dari `CRUD_FLOW.md` §1 yang berlaku di sini: isi `definition()` sungguhan, jangan `[]`. Factory model bertenant menerima `tenant_id` lewat `for()`/state — dan karena hook `creating` melempar kalau tidak ada tenant_id dan tidak ada context, factory **wajib** dipakai sebagai `Account::factory()->for($tenant)->create()`. Tambahkan state `->forTenant(Tenant $t)` yang sekaligus mengisi `created_by`.

---

## 11. Testing (Pest 5)

### 11.1 Prasyarat

`tests/Pest.php` saat ini punya `->use(RefreshDatabase::class)` **dalam keadaan ter-comment**. Uncomment — semua test domain butuh DB. DB test = SQLite `:memory:` (`phpunit.xml`), jadi migration harus SQLite-safe (poin `->change()` di §1.1 dan foreign key).

Tambahkan helper di `tests/Pest.php`:

```php
function tenantFor(User $user, TenantRole $role = TenantRole::Owner): Tenant;
function actingAsMemberOf(Tenant $tenant, TenantRole $role): User;   // login + set session tenant
function withTenant(Tenant $tenant, callable $fn): mixed;            // TenantContext::runFor
function assertAccountBalanceReconciles(Account $account): void;     // invariant §3.5
```

### 11.2 Test yang layak ditulis (prioritas menurun)

**A. Isolasi tenant — `tests/Feature/Tenancy/TenantIsolationTest.php`** (paling penting; ini data finansial)

- Transaksi tenant B tidak muncul di `transactions.index` milik tenant A.
- `GET /transactions/{id}/edit` untuk baris tenant B → **404**, bukan 403 (global scope + route binding).
- `PUT /transactions/{id}` untuk baris tenant B → 404.
- `StoreTransactionRequest` menolak `account_id` milik tenant lain (validasi, bukan cuma scope).
- **Query model bertenant di HTTP tanpa tenant aktif melempar `TenantContextMissingException`** — ini yang menjaga scope tidak jadi no-op diam-diam.
- Switch tenant mengubah isi listing.
- `POST /tenants/{tenant}/switch` ke tenant yang bukan miliknya → 404.

**B. Kebenaran saldo — `tests/Feature/Money/`**

- `TransferTest`: transfer 100rb → `from` −100rb, `to` +100rb, total saldo tenant tetap; transfer ke akun yang sama ditolak 422; transfer ke akun tenant lain ditolak.
- `TransactionBalanceTest`: create income/expense; update yang mengubah **amount**; update yang **memindah akun** (assert kedua akun benar); soft delete membalik saldo; restore menerapkan ulang. Setiap test diakhiri `assertAccountBalanceReconciles()`.
- `TransferUpdateTest`: ubah `from_account_id` → tiga akun terlibat, semuanya benar.
- Test rollback: paksa exception di tengah `CreateTransfer` → tidak ada baris transfer DAN tidak ada perubahan saldo.

**C. Policy — `tests/Feature/Authorization/RoleMatrixTest.php`**
Datasets Pest untuk matriks §5.1 — satu `it()` dengan `->with([...])` yang menyebut role, route, dan status yang diharapkan. Yang wajib ada (jalur **penolakan**, bukan cuma yang diizinkan, karena coverage tidak melihat ini):

- member tidak bisa create/update/delete account, category, invitation
- member bisa edit transaksi **miliknya sendiri** tapi 403 untuk milik orang lain
- admin tidak bisa lihat `/billing` (403), owner bisa
- admin tidak bisa mengeluarkan owner
- admin tidak bisa ubah role anggota

**D. Undangan — `tests/Feature/Invitations/InvitationFlowTest.php`**

- Owner mengundang → baris `tenant_invitations` dibuat, notifikasi terkirim (`Notification::fake()`).
- Undang ulang email sama → **jumlah baris tetap 1**, token berubah, token lama 404/410.
- Accept oleh user dengan email cocok → masuk `tenant_user`, status `accepted`.
- **Accept oleh user login dengan email berbeda → 403 dan tidak masuk `tenant_user`.** (Ini serangan pencurian invite dari PRD — test yang paling penting di file ini.)
- Token kedaluwarsa (`travel(8)->days()`) → status jadi `expired`, accept → 410.
- Undangan yang di-revoke → 404/410.
- Accept dua kali → idempotent, tidak duplikat pivot.
- User baru via Google + token di session → tidak dibuatkan tenant pribadi.

**E. Auth — `tests/Feature/Auth/GoogleLoginTest.php`**
Mock `Socialite::shouldReceive('driver->user')`:

- user baru → dibuat dengan `password = null`, tenant otomatis, jadi owner.
- email sudah ada (akun password) → **di-link**, bukan duplikat; jumlah user tetap.
- user Google-only mencoba login email/password → gagal (`authenticateUsing` menolak), tidak error PHP.
- Test Fortify yang sudah ada (`tests/Feature/Auth/*`) harus tetap hijau setelah `password` jadi nullable — jalankan sebagai regression gate.

**F. Registrasi & tenant — `tests/Feature/Tenancy/TenantCreationTest.php`**

- Register → 1 tenant, user jadi owner, 7 kategori default ada, subscription `free` `active`, `subdomain` unik terisi.
- User tanpa tenant mengakses `/dashboard` → redirect ke `tenants.create`.

**G. Dashboard & billing (ringan)**

- Angka `total_balance`/`income_this_month` benar dan **tidak** menghitung akun terarsip / transaksi terhapus.
- Toggle billing mengubah `subscriptions.plan_id`+`status`, dan member/admin tidak bisa memanggilnya.

Test yang **tidak** perlu ditulis untuk prototype: render tiap halaman Vue, CRUD kategori jalur bahagia yang trivial, snapshot formatting.

### 11.3 Gate CI

`composer ci:check` = `npm run check` → `vue-tsc` → `pint --test` → `phpstan level 7` → `artisan test`. Konsekuensi konkret yang menggigit saat implementasi:

- **PHPStan level 7** menganalisis `app/`, `database/`, `routes/`, `config/`. Semua relasi butuh anotasi generik (`@return BelongsTo<Tenant, $this>`), semua method butuh return type, `database/seeders` dan `database/factories` ikut dianalisis.
- **`npm run check` juga memeriksa Markdown** (per `CRUD_FLOW.md` §Before you push) — file `.md` baru bisa menggagalkan build karena formatting saja.
- **`vite.config.ts` set `lint.options.denyWarnings: true` dan `typeAware: true`** — warning TS = build merah. Tulis tipe props dengan benar sejak awal.
- `wayfinder:generate --with-form` wajib dijalankan setelah mengubah routes kalau dev server tidak berjalan; `formVariants: true` sudah aktif di `vite.config.ts`, jadi jangan lupa flag `--with-form` (tanpa itu `store.form()` hilang dan belasan halaman auth pecah).
- Tidak ada coverage gate di repo ini (`composer.json` tidak punya `test:coverage` / `bin/no-zero-coverage.php`), jadi tidak perlu pcov.

---

## 12. Urutan Commit / PR

Setiap PR harus hijau di `composer ci:check` sendiri. Ukuran target: 300–800 baris diff.

| #   | Judul PR                                                | Isi                                                                                                                                                                                                                         | Test                                                                                   |
| --- | ------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| 0   | `chore: initial commit of starter kit`                  | Commit repo apa adanya (belum ada commit sama sekali!). Wajib duluan supaya semua PR berikutnya punya diff yang bisa dibaca                                                                                                 | suite bawaan hijau                                                                     |
| 1   | `chore: project setup for fluxa`                        | `composer require laravel/socialite`; `config/fluxa.php`; `.env.example` (Google keys, `APP_TIMEZONE`, `APP_LOCALE=id`); uncomment `RefreshDatabase` di `tests/Pest.php`; `app/Enums/*`                                     | test enum tipis                                                                        |
| 2   | `feat(db): phase 1 migrations, models, factories`       | Semua migration §1, model §2.5, QueryBuilder §2.6, factory §10.4, `PlanSeeder`                                                                                                                                              | migration jalan di SQLite; factory smoke test                                          |
| 3   | `feat(tenancy): tenant context, global scope, switcher` | `TenantContext`, `TenantScope`, `BelongsToTenant`, `ResolveTenant`, `TenantContextMissingException`, `CreateTenant`+`SeedDefaultCategories`, route tenant, `tenants/Create.vue`, `TenantSwitcher.vue`, shared props Inertia | **TenantIsolationTest + TenantCreationTest** (PR paling penting untuk direview teliti) |
| 4   | `feat(auth): google oauth alongside fortify`            | Migration kolom users (bisa juga digabung ke #2), `Fortify::authenticateUsing`, Google controllers, `FindOrCreateGoogleUser`, `CompleteRegistration`, tombol Google di `auth/Login.vue` + `Register.vue`                    | GoogleLoginTest + regression auth lama                                                 |
| 5   | `feat(accounts): crud kantong`                          | Policy, FormRequest, Action, controller, halaman, `MoneyText`/`CurrencyInput`, `formatRupiah`                                                                                                                               | CRUD + policy member ditolak                                                           |
| 6   | `feat(categories): crud kategori`                       | Idem; default categories sudah ada dari #3                                                                                                                                                                                  | CRUD + policy                                                                          |
| 7   | `feat(transactions): crud pemasukan & pengeluaran`      | `AdjustAccountBalance` + 4 Action transaksi, filter index, halaman                                                                                                                                                          | **TransactionBalanceTest** (create/update/pindah akun/delete/restore)                  |
| 8   | `feat(transfers): transfer antar kantong`               | Action transfer + locking, halaman                                                                                                                                                                                          | **TransferTest** + test rollback                                                       |
| 9   | `feat(members): invite, roles, member management`       | Invitation actions, notification, `invitations/Show.vue`, `members/Index.vue`, `TenantMemberPolicy`                                                                                                                         | **InvitationFlowTest + RoleMatrixTest**                                                |
| 10  | `feat(billing): plan & mock upgrade`                    | `BillingController`, `ToggleSubscription`, `billing/Index.vue`                                                                                                                                                              | akses owner-only                                                                       |
| 11  | `feat(dashboard): ringkasan & chart`                    | `DashboardController`, `DonutChart`/`BarChart`, `Dashboard.vue`                                                                                                                                                             | angka ringkasan benar                                                                  |
| 12  | `chore(demo): demo seeder & polish mobile`              | `DemoSeeder`, empty state, responsive pass, README singkat                                                                                                                                                                  | manual QA                                                                              |

PR #3, #7, #8, #9 adalah yang butuh review paling serius — di situ letak isolasi data, integritas uang, dan otorisasi.

---

## 13. Risiko & Keputusan Terbuka

Yang sebaiknya diputuskan user **sebelum** implementasi dimulai:

1. **Konvensi CRUD — konfirmasi opsi (b+).** Rencana ini menolak install spatie-data/permission/media, lorisleiva actions, lacodix, dan TanStack Query. Kalau tujuan repo ini adalah menjadi produk (bukan prototype dibuang), mungkin lebih murah membayar biaya setup itu **sekarang** lewat skill `anthropic-skills:laravel-inertia-vue-crud` daripada memigrasi 8 resource nanti. Ini keputusan produk, bukan teknis.
2. **Observer vs Action untuk balance.** PRD tertulis "balance reversed via observer"; rencana ini pakai Action (§3.1). Butuh persetujuan eksplisit karena menyimpang dari dokumen.
3. **Unique index undangan.** Rencana ini memakai `unique(tenant_id, email)` penuh (§1.4), bukan partial unique PRD. Konsekuensi: tidak ada riwayat undangan lama per email. Terima atau tidak?
4. **`initial_balance` boleh diedit setelah akun dibuat?** Rencana ini mengunci field-nya di form edit. Kalau boleh diedit, ia harus jadi delta ke `balance` di dalam transaksi terkunci — tambah satu Action dan satu test.
5. **Hapus kategori yang masih dipakai transaksi** → tolak 422 (rekomendasi) atau set `category_id` null? Mempengaruhi laporan historis.
6. **User Google-only dan halaman Security.** `RequirePassword` middleware + `current_password` di hapus-akun akan buntu (§6.2). Prototype butuh flow "set password" atau cukup menyembunyikan menunya?
7. **Verifikasi email.** `App\Models\User` **tidak** implement `MustVerifyEmail`, tapi route memakai middleware `verified` dan Fortify mengaktifkan `Features::emailVerification()`. Saat ini `verified` lolos otomatis. Biarkan (prototype) atau aktifkan?
8. **`lockForUpdate()` adalah no-op di SQLite.** Test konkurensi tidak membuktikan apa-apa di CI (`DB_CONNECTION=sqlite` di `phpunit.xml`). Kalau target produksi MySQL/Postgres, sebaiknya ada satu workflow CI kedua dengan MySQL untuk test uang — atau terima bahwa lock hanya terverifikasi manual.
9. **Queue & tenant context.** `TenantScope` mematikan diri di console, termasuk `queue:work`. Phase 1 belum punya job bertenant, tapi begitu ada (misal kirim email undangan async), job itu **wajib** membawa `tenant_id` dan membungkus handle-nya dengan `runFor()`. Perlu ditulis sebagai aturan di CLAUDE.md/README sebelum job pertama muncul.
10. **`TenantInvitation` sengaja tidak bertrait `BelongsToTenant`** (§2.4). Ini benar tapi mudah "diperbaiki" salah oleh orang berikutnya. Butuh komentar besar di kelas + satu test yang gagal kalau trait ditambahkan.
11. **Chart tanpa library** berarti tanpa tooltip hover dan tanpa animasi. Kalau demo ke stakeholder butuh polish, naikkan ke `chart.js` di PR #11 (biaya ~2 jam).
12. **Nama field `subdomain` yang tidak dipakai.** Ada risiko orang mengira routing subdomain sudah jalan. Isi dengan nilai yang jelas placeholder dan beri label di UI settings ("Belum aktif — disiapkan untuk rilis berikutnya").
13. **Feature gating belum diimplementasi.** `plans.features.max_members`/`max_accounts` tersimpan tapi tidak ditegakkan di phase 1 ini. `PlanFeatureChecker` adalah hook satu baris di `InviteMember` dan `CreateAccount` — masuk sekarang atau nanti?

---

### File paling kritikal untuk implementasi

- `/home/toni2/Sites/fluxa/app/Models/Concerns/BelongsToTenant.php` + `/home/toni2/Sites/fluxa/app/Models/Scopes/TenantScope.php` (belum ada — inti isolasi data)
- `/home/toni2/Sites/fluxa/app/Support/TenantContext.php` + `/home/toni2/Sites/fluxa/app/Http/Middleware/ResolveTenant.php` (belum ada — resolusi tenant aktif)
- `/home/toni2/Sites/fluxa/app/Actions/Account/AdjustAccountBalance.php` (belum ada — satu-satunya penulis kolom `balance`)
- `/home/toni2/Sites/fluxa/app/Http/Middleware/HandleInertiaRequests.php` (ada — shared props tenant/role/abilities)
- `/home/toni2/Sites/fluxa/routes/web.php` (ada — peta middleware `tenant` vs non-tenant, §4.2)
- `/home/toni2/Sites/fluxa/app/Providers/FortifyServiceProvider.php` (ada — `authenticateUsing` untuk password nullable, §6.2)
- `/home/toni2/Sites/fluxa/tests/Pest.php` (ada — `RefreshDatabase` masih ter-comment, helper test tenant)
