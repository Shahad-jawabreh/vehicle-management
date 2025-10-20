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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the sensor/provider, e.g., 'Temperature Sensor', 'GPS Module'
            $table->string('type'); // e.g., 'Sensor', 'Actuator', 'Module'
            $table->string('data_key')->unique(); // Unique key for data reporting

            // One Provider belongs to one Device (Foreign Key for the 1:M relationship)
            $table->foreignId('device_id')->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
