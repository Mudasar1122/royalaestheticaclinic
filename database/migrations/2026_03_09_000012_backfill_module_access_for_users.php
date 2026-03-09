<?php

use Illuminate\Database\Migrations\Migration;
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
            return;
        }

        DB::table('users')
            ->whereNull('module_access')
            ->update([
                'module_access' => json_encode(['lead_management', 'campaign_management']),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: module_access data backfill should not be reverted.
    }
};
