<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CategoryFormData;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
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
     *
     * Gambar ikon diunggah lewat spatie medialibrary (koleksi `icon`, satu
     * file). `removeIcon` menghapus gambar yang ada, misal tombol bersihkan
     * di form.
     */
    public function handle(
        CategoryFormData $data,
        ?Category $category = null,
        ?UploadedFile $icon = null,
        bool $removeIcon = false,
    ): Category {
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
            $category->refresh();

            $this->applyIcon($category, $icon, $removeIcon);

            return $category;
        }

        $category = Category::create($data->toArray());

        $this->applyIcon($category, $icon, $removeIcon);

        return $category;
    }

    private function applyIcon(Category $category, ?UploadedFile $icon, bool $removeIcon): void
    {
        // Ikon single-file: medialibrary mengganti file lama OTOMATIS saat
        // koleksi bernama 'icon' menerima file baru — file lama dihapus
        // belakangan, setelah yang baru berhasil tersimpan. Menghapus koleksi
        // manual duluan bikin dua operasi disk terpisah yang di lingkungan ini
        // kadang bentrok di tengah proses.
        if ($removeIcon) {
            $category->clearMediaCollection('icon');

            return;
        }

        if ($icon !== null) {
            $category->addMedia($icon)->toMediaCollection('icon');
        }
    }
}
