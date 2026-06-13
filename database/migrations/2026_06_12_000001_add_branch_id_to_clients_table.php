<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->nullOnDelete();
            $table->index(['branch_id', 'name']);
        });

        $mainBranchId = DB::table('branches')->orderBy('id')->value('id');

        if (!$mainBranchId) {
            return;
        }

        DB::table('clients')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(100, function ($clients) use ($mainBranchId) {
                foreach ($clients as $client) {
                    $branchId = DB::table('credits')
                        ->where('client_id', $client->id)
                        ->whereNotNull('branch_id')
                        ->orderByDesc('id')
                        ->value('branch_id');

                    if (!$branchId && Schema::hasTable('other_incomes')) {
                        $branchId = DB::table('other_incomes')
                            ->where('client_id', $client->id)
                            ->whereNotNull('branch_id')
                            ->orderByDesc('id')
                            ->value('branch_id');
                    }

                    if (!$branchId && Schema::hasTable('accounts_payable')) {
                        $branchId = DB::table('accounts_payable')
                            ->where('client_id', $client->id)
                            ->whereNotNull('branch_id')
                            ->orderByDesc('id')
                            ->value('branch_id');
                    }

                    DB::table('clients')
                        ->where('id', $client->id)
                        ->update(['branch_id' => $branchId ?: $mainBranchId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'name']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
