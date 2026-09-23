<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('billing_cycle_start_day');
            $table->unsignedTinyInteger('billing_cycle_end_day');
            $table->unsignedSmallInteger('payment_due_offset_days')->default(0);
            $table->decimal('default_interest_rate_monthly', 5, 2)->default(0);
            $table->decimal('default_admin_fee_percentage', 5, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_details');
    }
};
