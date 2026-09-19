<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // PermissionSeeder wajib pertama: UserSeeder menugaskan role yang
        // definisinya dibuat di sana.
        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
