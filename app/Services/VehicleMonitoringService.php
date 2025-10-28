<?php
namespace App\Services;

use App\Models\{Vehicle, User, EventType, UserEvent, VehicleEventState};
use App\Notifications\VehicleEventNotification;
use Illuminate\Support\Facades\Log;

class VehicleMonitoringService
{

    /**
     * Check all monitoring rules for a vehicle
     */
    public function checkVehicle($vehicleId)
    {
        $vehicle = Vehicle::with(['user.company'])->find($vehicleId);

        if (!$vehicle || !$vehicle->user) {
            Log::warning("Vehicle or user not found: {$vehicleId}");
            return;
        }

        // Check all monitoring types
        $this->checkGeofences($vehicle);
        $this->checkSpeed($vehicle);
        //$this->checkTemperature($vehicle);
    }

    /**
     * Geofence monitoring
     */
    private function checkGeofences(Vehicle $vehicle)
    {
        $zones = $vehicle->user->company->zones ?? collect();

        if ($zones->isEmpty() || !$vehicle->location) {
            return;
        }

        foreach ($zones as $zone) {
            if (!$zone->location) continue;

            $distance = $this->calculateDistance(
                $vehicle->location->latitude,
                $vehicle->location->longitude,
                $zone->location->latitude,
                $zone->location->longitude
            );

            $isInside = $distance <= $zone->radius;
            $newState = ['is_inside' => $isInside, 'distance' => round($distance, 2)];
            $eventType = $isInside ? 'entered_zone' : 'left_zone';
            $state = VehicleEventState::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'event_type' => $eventType,
                    'reference_id' => $zone->id
                ],
                ['state_data' => $newState]
            );

            // Only notify if state changed
            if (!$state->hasChanged($newState)) {
                continue;
            }

            $state->update([
                'state_data' => $newState,
                'last_triggered_at' => now()
            ]);

            $this->sendNotification($vehicle, $eventType, [
                'zone_name' => $zone->name,
                'distance' => $newState['distance']
            ]);
        }
    }

    /**
     * Speed monitoring
     */
    private function checkSpeed(Vehicle $vehicle)
    {
        if (!isset($vehicle->speed)) {
            return;
        }
        $speedLimit = $vehicle->user->company->speed_limit ?? 120; // Default limit
        $isExceeding = $vehicle->speed > $speedLimit;

        $newState = [
            'is_exceeding' => $isExceeding,
            'current_speed' => $vehicle->speed,
            'limit' => $speedLimit
        ];
        $eventType = $isExceeding ? 'speed_exceeded' : 'speed_normal';

        $state = VehicleEventState::firstOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'event_type' => $eventType,
                'reference_id' => null
            ],
            ['state_data' => $newState]
        );

        // Notify on state change OR if still exceeding after cooldown
        $shouldNotify = $state->hasChanged($newState) ||
                       ($isExceeding && $state->canNotifyAgain(15));

        if (!$shouldNotify) {
            return;
        }

        $state->update([
            'state_data' => $newState,
            'last_triggered_at' => now()
        ]);

        if ($isExceeding) {
            // add to event user data to notifiy admin of user
            $this->sendNotification($vehicle, 'speed_exceeded', [
                'speed' => $vehicle->speed,
                'limit' => $speedLimit
            ]);
        }
    }

    /**
     * Temperature monitoring
     */
    // private function checkTemperature(Vehicle $vehicle)
    // {
    //     if (!isset($vehicle->engine_temperature)) {
    //         return;
    //     }

    //     $tempLimit = 95; // Celsius
    //     $isOverheating = $vehicle->engine_temperature > $tempLimit;

    //     $newState = [
    //         'is_overheating' => $isOverheating,
    //         'temperature' => $vehicle->engine_temperature
    //     ];

    //     $state = VehicleEventState::firstOrCreate(
    //         [
    //             'vehicle_id' => $vehicle->id,
    //             'event_type' => 'temperature',
    //             'reference_id' => null
    //         ],
    //         ['state_data' => $newState]
    //     );

    //     // Notify on state change OR if still overheating after cooldown
    //     $shouldNotify = $state->hasChanged($newState) ||
    //                    ($isOverheating && $state->canNotifyAgain(10));

    //     if (!$shouldNotify) {
    //         return;
    //     }

    //     $state->update([
    //         'state_data' => $newState,
    //         'last_triggered_at' => now()
    //     ]);

    //     if ($isOverheating) {
    //         $this->sendNotification($vehicle, 'engine_overheating', [
    //             'temperature' => $vehicle->engine_temperature,
    //             'limit' => $tempLimit
    //         ]);
    //     }
    // }

    /**
     * Send notification helper
     */
    private function sendNotification(Vehicle $vehicle, string $eventType, array $details)
    {
        $user = $vehicle->user;
        $notifyUser = User::find($user->created_by);

        if (!$notifyUser) {
            Log::warning("Notify user not found: {$user->created_by}");
            return;
        }

        $event = EventType::firstOrCreate(['type' => $eventType]);

        $messages = [
            'entered_zone' => "دخلت المنطقة {$details['zone_name']}",
            'left_zone' => "خرجت من المنطقة {$details['zone_name']}",
            'speed_exceeded' => "تجاوزت السرعة المحددة ({$details['speed']} km/h)",
            'engine_overheating' => "حرارة المحرك مرتفعة ({$details['temperature']}°C)",
        ];

        $userEvent = UserEvent::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'details' => 'dd',
            'user_to_notify_id' => $user->created_by,
            'is_notified' => false,
        ]);

        try {
            $notifyUser->notify(new VehicleEventNotification($userEvent, $vehicle));
            $userEvent->update(['is_notified' => true]);
            Log::info("✓ Notification sent: {$eventType} for vehicle {$vehicle->id}");
        } catch (\Exception $e) {
            Log::error("Notification failed: " . $e->getMessage());
        }
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;
        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }
}
