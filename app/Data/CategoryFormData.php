<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CategoryType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Bentuk kategori masuk, apa yang dikirim form create/update.
 *
 * Sengaja tanpa id: create tidak punya id untuk dibawa, dan target update
 * datang dari route binding, bukan dari payload.
 */
#[TypeScript]
class CategoryFormData extends Data
{
    public function __construct(
        #[Required, Max(50)]
        public string $name,
        // Class constant, bukan CategoryType::values(): PHP melarang pemanggilan
        // method di argumen attribute.
        #[Enum(CategoryType::class)]
        public CategoryType $type,
        #[Nullable, Max(64)]
        public ?string $icon,
        public bool $is_default = false,
    ) {}
}
