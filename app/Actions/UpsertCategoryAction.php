<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CategoryFormData;
use App\Models\Category;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class UpsertCategoryAction
{
    use AsAction;

    /**
     * Buat kategori, atau perbarui yang dikirim pemanggil.
     *
     * Target update datang dari route binding, bukan dari payload: id di dalam
     * body akan membiarkan siapa pun menimpa baris lain hanya dengan menebak
     * id-nya. `tenant_id` diisi hook creating pada trait BelongsToTenant.
     */
    public function handle(CategoryFormData $data, ?Category $category = null): Category
    {
        $query = Category::query()->whereNameAndType($data->name, $data->type);

        if ($category instanceof Category) {
            $query->whereKeyNot($category->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Nama kategori sudah dipakai untuk jenis ini.',
            ]);
        }

        if ($category instanceof Category) {
            $category->update($data->toArray());

            return $category;
        }

        return Category::create($data->toArray());
    }
}
