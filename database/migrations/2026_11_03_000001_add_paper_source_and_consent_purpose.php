<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PRD (2026-11-03): visitors.source marks rows an agent typed in from a paper
 * sign-in sheet; consents.purpose records what was agreed to. Also adds the
 * draft paper-sheet wording as its own immutable consent_texts row. Its version
 * starts with "paper-", which ConsentText::current() skips, so it can never
 * appear on the online check-in form.
 */
return new class extends Migration
{
    private const PAPER_DRAFT_BODY = 'By ticking "OK to text" on my row, I agree to receive a text message from {agent_name}, {brokerage}, with the listing link for this property. Message and data rates may apply. I can withdraw my consent at any time by replying STOP.';

    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->enum('source', ['qr', 'paper'])->default('qr')->after('event_id');
        });

        Schema::table('consents', function (Blueprint $table) {
            $table->enum('purpose', ['checkin', 'feedback', 'marketing'])->default('checkin')->after('channel');
        });

        if (! DB::table('consent_texts')->where('version', 'paper-v0-draft')->exists()) {
            DB::table('consent_texts')->insert([
                'version' => 'paper-v0-draft',
                'body' => self::PAPER_DRAFT_BODY,
                'effective_from' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
