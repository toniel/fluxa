<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CategoryType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Bentuk kategori keluar, satu baris yang sudah ada di database.
 */
#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public CategoryType $type,
        public ?string $icon,
        public ?string $color,
        public ?string $emoji,
        public string $icon_url,
        public bool $is_default,
    ) {}
}
