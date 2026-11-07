<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // is_admin is never mass-assignable; it is set only by `scanin:make-admin`.
            $table->boolean('is_admin')->default(false);
            // Suspended: no new events, no texts, check-in pages and short links go dark.
            $table->dateTime('suspended_at')->nullable();
            // Pro granted by the owner (beta testers etc.), not paid through Stripe.
            $table->dateTime('comped_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'suspended_at', 'comped_at']);
        });
    }
};
