<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'transfer_date']);
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('client_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'granted_date']);
        });

        Schema::table('other_incomes', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('client_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'income_date']);
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique('daily_closings_closing_date_unique');
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->unique(['branch_id', 'closing_date'], 'daily_closings_branch_date_unique');
        });

        Schema::table('cash_box_initials', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'date']);
        });

        $mainBranchId = DB::table('branches')->orderBy('id')->value('id');

        if ($mainBranchId) {
            DB::table('transfers')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('credits')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('other_incomes')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('daily_closings')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
            DB::table('cash_box_initials')->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        }
    }

    public function down(): void
    {
        Schema::table('cash_box_initials', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'date']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique('daily_closings_branch_date_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique('closing_date');
        });

        Schema::table('other_incomes', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'income_date']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'granted_date']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'transfer_date']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
