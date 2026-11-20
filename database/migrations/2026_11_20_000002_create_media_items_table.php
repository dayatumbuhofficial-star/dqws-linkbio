<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_items')) {
            Schema::create('media_items', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('file_path');
                $table->string('file_type', 20)->default('image'); // 'image' or 'video'
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};
