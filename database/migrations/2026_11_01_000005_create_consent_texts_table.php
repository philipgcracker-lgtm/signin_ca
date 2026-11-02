<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_texts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->string('version', 20)->unique();
            $table->text('body');
            $table->dateTime('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_texts');
    }
};
