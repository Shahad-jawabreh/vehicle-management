<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_to_notify_id')->constrained('users')->onDelete('cascade');
            $table->string('reference_id')->nullable();

            // Data fields
            $table->json('details')->nullable();
            $table->json('state_data')->nullable();

            // Status
            $table->boolean('is_notified')->default(false);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();

            $table->index(['vehicle_id', 'event_id', 'reference_id'], 'vehicle_event_ref_index');
            $table->index('user_to_notify_id');
            $table->index('last_triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
