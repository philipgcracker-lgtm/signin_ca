<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('agent_id');
            $table->string('slug', 64)->unique();
            $table->string('address_street', 255);
            $table->string('city', 100);
            $table->char('province', 2)->default('ON');
            $table->string('postal_code', 7)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->decimal('bathrooms', 3, 1)->nullable();
            $table->string('blurb', 500)->nullable();
            $table->text('primary_image_url')->nullable();
            $table->text('listing_url');
            // PRD declares seller_id with no foreign key; kept as specified.
            $table->integer('seller_id')->nullable();
            $table->dateTime('event_start');
            $table->dateTime('event_end');
            $table->enum('status', ['upcoming', 'live', 'ended', 'cancelled'])->default('upcoming');
            $table->enum('post_event_redirect', ['listing', 'agent_profile', 'custom'])->default('listing');
            $table->text('custom_redirect_url')->nullable();
            $table->dateTime('recap_sent_at')->nullable();
            $table->text('recap_pdf_url')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();

            $table->index('agent_id', 'idx_events_agent');
            $table->index(['event_start', 'event_end'], 'idx_events_window');
            $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
