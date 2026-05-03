<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {
            $table->foreignId('credit_id')->nullable()->after('client_id')->constrained('credits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('other_incomes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_id');
        });
    }
};