<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            // bigIncrements, bukan string/UUID bawaan stancl. Lihat alasannya di
            // config/tenancy.php pada 'id_generator'.
            $table->id();

            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            // Kolom milik stancl (HasDataColumn). Tidak dipakai Fluxa dan tidak
            // boleh diisi data domain: isinya tidak bisa di-index maupun di-query
            // dengan benar. Kolom nyata didaftarkan di Tenant::getCustomColumns().
            $table->json('data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
