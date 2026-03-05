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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 30)->default('staff')->after('password')->index();
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('profile_photo_path')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('role')->index();
        });

        // Promote the oldest user to admin so user management is accessible after migration.
        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId !== null) {
            DB::table('users')->where('id', $firstUserId)->update(['role' => 'admin']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'role',
                'phone',
                'profile_photo_path',
                'is_active',
            ]);
        });
    }
};
