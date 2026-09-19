<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table): void {
            $table->id();

            // LABEL subdomain saja ('keluarga-budi-a1b2'), bukan domain penuh.
            // InitializeTenancyBySubdomain mencari label; menyimpan
            // 'keluarga-budi-a1b2.fluxa.test' di sini membuat setiap request
            // tenant menjadi 404 tanpa pesan yang menjelaskan apa pun.
            // Batas 63 karakter mengikuti panjang maksimum satu label DNS.
            $table->string('domain', 63)->unique();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);

            // Subdomain lama tidak dihapus saat tenant pindah ke custom
            // subdomain; barisnya ditandai di sini untuk redirect 301.
            $table->string('redirects_to', 63)->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
