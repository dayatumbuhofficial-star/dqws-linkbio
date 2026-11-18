<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->default('dqws');
            $table->string('title');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->json('draft_theme')->nullable();
            $table->json('published_theme')->nullable();
            $table->json('draft_blocks')->nullable();
            $table->json('published_blocks')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_pages');
    }
};
