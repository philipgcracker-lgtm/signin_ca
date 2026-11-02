<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->string('code', 8)->primary();
            $table->text('target_url');
            $table->integer('event_id')->nullable();
            $table->integer('visitor_id')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('visitor_id')->references('id')->on('visitors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
