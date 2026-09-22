<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika query model bertenant berjalan tanpa tenant aktif.
 *
 * Global scope sengaja memilih gagal berisik daripada diam: "tidak ada tenant"
 * bukan alasan untuk mengembalikan seluruh baris semua tenant. Konsol
 * (seeder, factory, test unit) memakai TenantContext::runFor(), yang membuat
 * pengecualian lewat creating hook di trait BelongsToTenant.
 */
final class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $model)
    {
        parent::__construct(
            "Permintaan ke data milik tenant tanpa tenant aktif untuk model {$model}.",
        );
    }
}
