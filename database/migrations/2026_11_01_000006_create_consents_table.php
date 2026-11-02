<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('visitor_id');
            $table->integer('agent_id');
            $table->integer('consent_text_id');
            $table->enum('channel', ['sms', 'email']);
            $table->boolean('granted');
            $table->binary('ip_address', 16)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('revoked_at')->nullable();

            $table->foreign('visitor_id')->references('id')->on('visitors')->cascadeOnDelete();
            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
            $table->foreign('consent_text_id')->references('id')->on('consent_texts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
