<?php

namespace App\Jobs;
use App\Services\VehicleMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAllVehiclesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $vehicleId;

    public function __construct($vehicleId)
    {
        $this->vehicleId = $vehicleId;
    }

    public function handle(VehicleMonitoringService $vehicleMonitoringService)
    {
        try {
            $vehicleMonitoringService->checkVehicle($this->vehicleId);
        } catch (\Throwable $e) {
            Log::error("Job failed for vehicle {$this->vehicleId}: " . $e->getMessage());
        }
    }
}
