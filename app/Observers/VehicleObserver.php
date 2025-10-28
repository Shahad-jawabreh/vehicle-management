<?php
namespace App\Observers;

use App\Models\Vehicle;
use App\Jobs\CheckAllVehiclesJob;

class VehicleObserver
{
    public function updated(Vehicle $vehicle)
    {
        if ($vehicle->isDirty(['location', 'speed'])) {
            CheckAllVehiclesJob::dispatch($vehicle->id);
        }
    }
}
