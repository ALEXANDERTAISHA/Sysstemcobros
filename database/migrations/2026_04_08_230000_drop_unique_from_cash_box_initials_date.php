<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_box_initials', function (Blueprint $table) {
            $table->dropUnique('cash_box_initials_date_unique');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::table('cash_box_initials', function (Blueprint $table) {
            $table->dropIndex('cash_box_initials_date_index');
            $table->unique('date');
        });
    }
};
