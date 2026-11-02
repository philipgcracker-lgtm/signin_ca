<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('event_id');
            $table->string('full_name', 255);
            $table->string('phone_e164', 16);
            $table->string('email', 255)->nullable();
            $table->boolean('working_with_agent')->nullable();
            $table->enum('buying_timeline', ['immediate', '1_3_months', '3_6_months', 'browsing'])->nullable();
            $table->boolean('pre_approved')->nullable();
            $table->boolean('sms_opt_out')->default(false);
            $table->unsignedTinyInteger('feedback_score')->nullable();
            $table->text('feedback_notes')->nullable();
            $table->dateTime('feedback_at')->nullable();
            $table->binary('ip_address', 16)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('anonymized_at')->nullable();

            $table->index('event_id', 'idx_visitors_event');
            $table->index('phone_e164', 'idx_visitors_phone');
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
