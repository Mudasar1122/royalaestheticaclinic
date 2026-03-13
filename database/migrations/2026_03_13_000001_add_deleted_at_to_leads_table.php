<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leads', 'deleted_at')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('leads', 'deleted_at')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
