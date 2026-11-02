<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('visitor_id')->nullable();
            $table->integer('event_id')->nullable();
            $table->enum('direction', ['out', 'in']);
            $table->enum('channel', ['sms', 'email']);
            $table->enum('kind', ['checkin', 'feedback', 'recap', 'inbound', 'other']);
            $table->string('to_addr', 255);
            $table->string('from_addr', 255);
            $table->text('body');
            $table->string('provider', 20);
            $table->string('provider_message_id', 64)->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'undelivered', 'received'])->default('queued');
            $table->unsignedSmallInteger('cost_cents')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->index('visitor_id', 'idx_messages_visitor');
            $table->index('provider_message_id', 'idx_messages_provider');
            $table->foreign('visitor_id')->references('id')->on('visitors')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
