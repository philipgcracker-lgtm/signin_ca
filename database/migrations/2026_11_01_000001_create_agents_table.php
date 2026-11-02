<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->char('uuid', 36)->unique();
            $table->string('full_name', 255);
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('phone', 20)->nullable();
            $table->string('brokerage_name', 255)->nullable();
            $table->text('headshot_url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->enum('subscription_tier', ['free', 'pro'])->default('free');
            $table->unsignedTinyInteger('retention_months')->default(24);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
