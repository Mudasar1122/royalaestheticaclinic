<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'module_access')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('module_access')->nullable()->after('role');
            });

            $allModules = json_encode(['lead_management', 'campaign_management']);

            DB::table('users')
                ->whereNull('module_access')
                ->update([
                    'module_access' => $allModules,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'module_access')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('module_access');
            });
        }
    }
};
