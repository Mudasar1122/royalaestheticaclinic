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
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable();
            $table->string('normalized_phone', 32)->nullable()->unique();
            $table->string('default_source', 30)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('external_id');
            $table->string('display_name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_id']);
            $table->index(['contact_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_identities');
        Schema::dropIfExists('contacts');
    }
};
