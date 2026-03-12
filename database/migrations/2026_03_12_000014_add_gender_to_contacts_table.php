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
        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('gender', 10)->default('female');
        });

        DB::table('contacts')
            ->where(function ($query): void {
                $query
                    ->whereNull('gender')
                    ->orWhere('gender', '');
            })
            ->update([
                'gender' => 'female',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('gender');
        });
    }
};
