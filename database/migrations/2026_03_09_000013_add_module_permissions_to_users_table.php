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
        if (!Schema::hasColumn('users', 'module_permissions')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->json('module_permissions')->nullable()->after('module_access');
            });
        }

        $defaultPermissions = json_encode([
            'lead_management' => [
                'view_leads',
                'create_lead',
                'edit_lead',
                'manage_followups',
                'mark_booked',
                'send_whatsapp',
            ],
            'campaign_management' => [
                'view_campaigns',
                'send_email_campaign',
                'send_whatsapp_campaign',
            ],
        ]);

        DB::table('users')
            ->whereNull('module_permissions')
            ->update([
                'module_permissions' => $defaultPermissions,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'module_permissions')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('module_permissions');
            });
        }
    }
};
