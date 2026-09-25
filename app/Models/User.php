<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\QueryBuilders\UserQueryBuilder;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $google_id
 * @property string|null $avatar
 * @property bool $is_super_admin
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static UserQueryBuilder query()
 *
 * @mixin UserQueryBuilder
 */
#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[UseEloquentBuilder(UserQueryBuilder::class)]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasFilters, HasRoles, IsSearchable, IsSortable, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Guard eksplisit supaya spatie tidak menebaknya dari konfigurasi auth.
     */
    protected string $guard_name = 'web';

    /** @var list<string> */
    protected $searchable = ['name', 'email'];

    /** @var array<string, string|null> */
    protected $sortable = ['name' => null, 'created_at' => null];

    /**
     * Tenant tempat user ini menjadi anggota.
     *
     * Keanggotaan dan role adalah dua hal terpisah: tabel pivot ini menjawab
     * "anggota tenant mana, sejak kapan", sedangkan role-nya hidup di
     * model_has_roles milik spatie yang ter-scope per tenant.
     *
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * Akun Google-only (daftar lewat OAuth) tidak punya password: jalur
     * login password tertutup untuknya, bukan error.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Super-admin platform: fakta lintas tenant, disimpan sebagai kolom
     * (bukan role spatie — role spatie di repo ini ter-scope per tenant,
     * sedangkan fakta ini harus berlaku di central tanpa konteks team).
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
