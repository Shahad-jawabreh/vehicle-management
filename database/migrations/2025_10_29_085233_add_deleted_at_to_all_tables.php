<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
{
    $tables = collect(DB::select('SHOW TABLES'))
        ->map(fn($t) => array_values((array)$t)[0]);

    foreach ($tables as $table) {
        if ($table === 'migrations') continue;

        if (!Schema::hasColumn($table, 'deleted_at')) {
            Schema::table($table, function (Illuminate\Database\Schema\Blueprint $table) {
                $table->timestamp('deleted_at')->nullable();
            });
        }
    }
}

public function down(): void
{
    $tables = collect(DB::select('SHOW TABLES'))
        ->map(fn($t) => array_values((array)$t)[0]);

    foreach ($tables as $table) {
        if (Schema::hasColumn($table, 'deleted_at')) {
            Schema::table($table, function (Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
}

};
