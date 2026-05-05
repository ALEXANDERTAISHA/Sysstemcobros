<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('other_incomes', 'credit_id')) {
            return;
        }

        Schema::table('other_incomes', function (Blueprint $table) {
            $table->foreignId('credit_id')->nullable()->after('client_id')->constrained('credits')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('other_incomes', 'credit_id')) {
            return;
        }

        Schema::table('other_incomes', function (Blueprint $table) {
            $table->dropForeign(['credit_id']);
            $table->dropColumn('credit_id');
        });
    }
};
