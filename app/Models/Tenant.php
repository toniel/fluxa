<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\TenantPolicy;
use App\QueryBuilders\TenantQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property int $id
 * @property string $name
 * @property int $owner_id
 * @property-read Domain[] $domains
 *
 * @method static TenantQueryBuilder query()
 */
#[Fillable(['name', 'owner_id'])]
#[UseEloquentBuilder(TenantQueryBuilder::class)]
#[UsePolicy(TenantPolicy::class)]
class Tenant extends BaseTenant
{
    use HasDomains;

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * Kolom nyata di tabel tenants.
     *
     * Wajib memuat SETIAP kolom nyata. Atribut yang tidak terdaftar di sini
     * dijejalkan stancl ke kolom `data` JSON tanpa error apa pun — kolom
     * aslinya sekadar tetap NULL.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'owner_id'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Anggota tenant. Pivot ini hanya menjawab keanggotaan; role-nya ada di
     * model_has_roles yang ter-scope per tenant.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function primaryDomain(): ?Domain
    {
        return Domain::query()->forTenant($this)->primary()->first();
    }

    public function subdomain(): ?string
    {
        return $this->primaryDomain()?->domain;
    }

    /**
     * URL absolut ke subdomain tenant ini, dipakai tenant switcher dan setiap
     * redirect lintas origin dari central domain.
     */
    public function url(string $path = '/'): string
    {
        $scheme = str_starts_with((string) config('app.url'), 'https') ? 'https' : 'http';
        $central = config('tenancy.central_domains')[0];

        return $scheme.'://'.$this->subdomain().'.'.$central.$path;
    }
}
