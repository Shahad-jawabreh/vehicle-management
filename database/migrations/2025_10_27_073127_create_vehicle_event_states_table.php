<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicle_event_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // 'zone', 'speed', 'temperature', etc.
            $table->string('reference_id')->nullable(); // zone_id for geofence events
            $table->json('state_data'); // Flexible JSON for any state
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'event_type', 'reference_id']);
            $table->index(['vehicle_id', 'event_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_event_states');
    }
};
