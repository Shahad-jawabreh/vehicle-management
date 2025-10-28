<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('company_zones', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
        $table->double('radius')->default(500);
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->timestamps();
    });
    DB::statement('ALTER TABLE company_zones ADD location POINT');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_zones');
    }
};
