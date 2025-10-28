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
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->json('details')->nullable();
        $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
        $table->boolean('is_notified')->default(false);
        $table->foreignId('user_to_notify_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
        $table->string('reference_id')->nullable(); // zone_id for geofence events
        $table->json('state_data'); // Flexible JSON for any state
        $table->timestamp('last_triggered_at')->nullable();
        $table->timestamps();
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
