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
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('source_platform', 30)->index();
            $table->string('status', 20)->default('open')->index();
            $table->string('stage', 30)->default('initial')->index();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'status']);
        });

        Schema::create('lead_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30)->index();
            $table->string('activity_type', 30)->index();
            $table->string('direction', 20)->default('inbound');
            $table->string('platform_message_id')->nullable();
            $table->longText('message_text')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('happened_at');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['platform', 'platform_message_id'], 'lead_activities_platform_message_unique');
            $table->index(['lead_id', 'happened_at']);
        });

        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_type', 40);
            $table->string('stage_snapshot', 30);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('due_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->string('summary')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'status', 'due_at']);
        });

        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 30);
            $table->string('event_id');
            $table->string('event_type', 100)->nullable();
            $table->json('payload');
            $table->string('status', 20)->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'event_id']);
            $table->index(['platform', 'status', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
