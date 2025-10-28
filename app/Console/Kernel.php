<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\CheckAllVehiclesJob;
use App\Models\Vehicle;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the vehicle position check every minute
        $schedule->call(function () {
            // Get all active vehicles
            $vehicles = Vehicle::where('is_active', true)->get();

            foreach ($vehicles as $vehicle) {
                // Dispatch a job for each vehicle
                dispatch(new CheckAllVehiclesJob($vehicle->id));
            }
        })->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
