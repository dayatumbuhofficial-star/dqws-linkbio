<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bio_page_id')->constrained('bio_pages')->cascadeOnDelete();
            $table->string('event_type', 20)->index(); // 'view' or 'click'
            $table->string('block_id', 100)->nullable()->index();
            $table->string('block_label')->nullable();
            $table->string('block_type', 50)->nullable();
            $table->text('target_url')->nullable();
            $table->string('visitor_hash', 64)->index();
            $table->string('referrer', 50)->nullable()->index();
            $table->string('utm_source', 100)->nullable()->index();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('device_type', 20)->default('Mobile')->index();
            $table->string('os', 30)->default('Lainnya')->index();
            $table->string('browser', 50)->default('Lainnya');
            $table->timestamps();

            $table->index(['bio_page_id', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_analytics_events');
    }
};
