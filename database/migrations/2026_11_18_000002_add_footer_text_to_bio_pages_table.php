<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->string('footer_text')->nullable()->default('© 2026 DQWS Link-in-Bio')->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            $table->dropColumn('footer_text');
        });
    }
};
