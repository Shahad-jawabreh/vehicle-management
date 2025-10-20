<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Provider;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with relational data.
     */
    public function run(): void
    {
        // 1. Clear existing data to ensure a fresh start every time
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Vehicle::truncate();
        Device::truncate();
        Provider::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Seed the relational structure for 20 Vehicles
        // This is the most efficient way to seed nested relationships.
        Vehicle::factory()
            ->count(20) // Create 20 base Vehicles

            // Each Vehicle will have 2 to 4 Devices
            ->has(
                Device::factory()
                    ->count(rand(2, 4))

                    // Each Device will have 3 to 6 Providers (Sensors)
                    ->has(
                        Provider::factory()->count(rand(3, 6))
                    , 'providers') // use 'providers' to explicitly name the relationship
            , 'devices') // use 'devices' to explicitly name the relationship

            ->create();

        echo "Database seeded successfully: 20 Vehicles created with associated Devices and Providers.\n";
    }
}
