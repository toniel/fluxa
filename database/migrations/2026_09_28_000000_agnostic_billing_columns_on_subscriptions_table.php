<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom khusus Xendit diganti penanda generik supaya ganti provider
     * (Midtrans, Duitku, ...) tidak butuh migrasi: cukup adapter baru.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('xendit_customer_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('xendit_subscription_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway', 24)->default('xendit')->after('status');
            $table->string('external_customer_id')->nullable()->after('gateway');
            $table->string('external_subscription_id')->nullable()->after('external_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('gateway');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('external_customer_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('external_subscription_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('xendit_customer_id')->nullable();
            $table->string('xendit_subscription_id')->nullable();
        });
    }
};
