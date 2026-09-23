<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['tenant_id', 'category_id', 'transaction_date']);
            $table->index(['tenant_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
