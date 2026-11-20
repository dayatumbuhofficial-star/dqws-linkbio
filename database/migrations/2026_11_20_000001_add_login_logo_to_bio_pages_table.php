<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('bio_pages', 'login_logo')) {
                $table->string('login_logo')->nullable()->after('avatar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bio_pages', function (Blueprint $table) {
            if (Schema::hasColumn('bio_pages', 'login_logo')) {
                $table->dropColumn('login_logo');
            }
        });
    }
};
