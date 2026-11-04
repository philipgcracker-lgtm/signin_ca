<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD (2026-11-03): the agent's own Google review link, shown to every visitor
 * on the feedback thank-you page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->text('google_review_url')->nullable()->after('headshot_url');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('google_review_url');
        });
    }
};
