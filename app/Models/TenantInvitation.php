<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\TenantRole;
use App\Policies\TenantInvitationPolicy;
use App\QueryBuilders\TenantInvitationQueryBuilder;
use Database\Factories\TenantInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Undangan anggota tenant. SENGAJA tanpa BelongsToTenant: halaman terima
 * hidup di luar tenant context (pengklik belum tentu punya tenant aktif),
 * jadi scoping eksplisit lewat builder (forTenant/byToken), bukan global
 * scope. Jangan "memperbaiki" dengan menambah trait.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $email
 * @property TenantRole $role
 * @property string $token
 * @property int $invited_by
 * @property InvitationStatus $status
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static TenantInvitationQueryBuilder query()
 *
 * @mixin TenantInvitationQueryBuilder
 */
#[Fillable(['tenant_id', 'email', 'role', 'token', 'invited_by', 'status', 'expires_at'])]
#[UseEloquentBuilder(TenantInvitationQueryBuilder::class)]
#[UsePolicy(TenantInvitationPolicy::class)]
class TenantInvitation extends Model
{
    /** @use HasFactory<TenantInvitationFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'role' => TenantRole::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::Pending && ! $this->isExpired();
    }

    public function matches(User $user): bool
    {
        return Str::lower($user->email) === Str::lower($this->email);
    }
}
