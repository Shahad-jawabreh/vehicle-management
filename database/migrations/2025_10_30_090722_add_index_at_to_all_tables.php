<?php

// database/migrations/xxxx_add_backup_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes to all tables with timestamps
        Schema::table('users', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });

        Schema::table('company_zones', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });
        Schema::table('user_events', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->index(['updated_at', 'created_at', 'deleted_at']);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['created_at']);
        });
        // ... etc
    }
};
