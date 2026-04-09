<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $principalBranchId = DB::table('branches')->insertGetId([
            'name' => 'Sucursal Principal',
            'code' => 'MAIN',
            'address' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('operator')->after('email');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('branch_id');
        });

        $firstUser = DB::table('users')->orderBy('id')->first();

        if ($firstUser) {
            DB::table('users')->where('id', $firstUser->id)->update([
                'role' => 'super_admin',
                'branch_id' => $principalBranchId,
                'is_active' => true,
            ]);

            DB::table('users')->where('id', '!=', $firstUser->id)->update([
                'role' => 'operator',
                'branch_id' => $principalBranchId,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('role');
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('branches');
    }
};
