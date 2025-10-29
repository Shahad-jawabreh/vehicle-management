<?php
namespace App\Services;

use App\Models\{Vehicle, User, EventType, UserEvent};
use App\Notifications\VehicleEventNotification;
use Illuminate\Support\Facades\Log;
use App\Models\CompanyZone;

class VehicleMonitoringService
{
    public function checkVehicle($vehicleId)
    {
        $vehicle = Vehicle::with(['user.company.zones'])->find($vehicleId);

        if (!$vehicle || !$vehicle->user) {
            Log::warning("Vehicle or user not found: {$vehicleId}");
            return;
        }

        $this->checkGeofences($vehicle);
        $this->checkSpeed($vehicle);
    }
    
    private function checkGeofences(Vehicle $vehicle)
    {
        $zone_id = $vehicle->user->zone_id;

        if (!$zone_id || !$vehicle->location) {
            return;
        }

        $zone = CompanyZone::find($zone_id);

        if (!$zone || !$zone->location) {
            return;
        }

        $distance = $this->calculateDistance(
            $vehicle->location->latitude,
            $vehicle->location->longitude,
            $zone->location->latitude,
            $zone->location->longitude
        );

        $isInside = $distance <= $zone->radius;

        // ✅ نخزن فقط الحالة المهمة
        $newState = ['is_inside' => $isInside];

        $lastEvent = UserEvent::where('vehicle_id', $vehicle->id)
            ->where('reference_id', $zone->id)
            ->whereHas('eventType', function($q) {
                $q->whereIn('type', ['entered_zone', 'left_zone']);
            })
            ->latest()
            ->first();

        // ✅ مقارنة بسيطة
        if ($lastEvent && !$lastEvent->hasChanged($newState)) {
            Log::info('⏭️ Geofence: No state change', [
                'vehicle_id' => $vehicle->id,
                'zone_id' => $zone->id,
                'state' => $isInside ? 'inside' : 'outside'
            ]);
            return;
        }

        $eventType = $isInside ? 'entered_zone' : 'left_zone';
        $event = EventType::firstOrCreate(['type' => $eventType]);

        Log::info('📘 Geofence state changed', [
            'event_type' => $eventType,
            'vehicle_id' => $vehicle->id,
            'zone_id' => $zone->id
        ]);

        $userEvent = UserEvent::create([
            'vehicle_id' => $vehicle->id,
            'event_id' => $event->id,
            'reference_id' => $zone->id,
            'user_to_notify_id' => $vehicle->user->created_by,
            'details' => [
                'message' => "Vehicle {$vehicle->license_plate} " .
                            ($isInside ? 'دخلت' : 'خرجت من') . " المنطقة {$zone->name}",
                'vehicle_license_plate' => $vehicle->license_plate,
                'zone_name' => $zone->name,
                'distance' => round($distance, 2) // ✅ المسافة في details فقط (للعرض)
            ],
            'state_data' => $newState, // ✅ فقط: {'is_inside': true/false}
            'last_triggered_at' => now(),
            'is_notified' => false,
        ]);

        Log::info('✅ Geofence event created', ['id' => $userEvent->id]);

        $this->sendNotification($userEvent, $vehicle);
    }

    private function checkSpeed(Vehicle $vehicle)
    {
        if (!isset($vehicle->speed)) {
            return;
        }

        $speedLimit = $vehicle->user->company->speed_limit ?? 120;
        $isExceeding = $vehicle->speed > $speedLimit;

        // ✅ نخزن فقط الحالة المهمة
        $newState = ['is_exceeding' => $isExceeding];

        $lastEvent = UserEvent::where('vehicle_id', $vehicle->id)
            ->whereNull('reference_id')
            ->whereHas('eventType', function($q) {
                $q->where('type', 'speed_exceeded');
            })
            ->latest()
            ->first();

        if (!$isExceeding) {
            Log::info('ℹ️ Speed is normal', [
                'vehicle_id' => $vehicle->id,
                'speed' => $vehicle->speed
            ]);
            return;
        }

        // ✅ منطق بسيط وواضح
        $shouldNotify = false;
        $reason = '';

        if (!$lastEvent) {
            $shouldNotify = true;
            $reason = 'First time exceeding';
        } elseif ($lastEvent->hasChanged($newState)) {
            // لن يحدث هذا لأننا نخزن فقط true/false
            // لكن نتركه للأمان
            $shouldNotify = true;
            $reason = 'State changed';
        } elseif ($lastEvent->canNotifyAgain(15)) {
            $shouldNotify = true;
            $reason = '15 minutes passed, still exceeding';
        }

        if (!$shouldNotify) {
            Log::info('⏭️ Speed: No notification needed', [
                'vehicle_id' => $vehicle->id,
                'speed' => $vehicle->speed,
                'minutes_since_last' => $lastEvent->last_triggered_at->diffInMinutes(now())
            ]);
            return;
        }

        $event = EventType::firstOrCreate(['type' => 'speed_exceeded']);

        Log::info('🚨 Speed event triggered', [
            'vehicle_id' => $vehicle->id,
            'speed' => $vehicle->speed,
            'reason' => $reason
        ]);

        $userEvent = UserEvent::create([
            'vehicle_id' => $vehicle->id,
            'event_id' => $event->id,
            'reference_id' => null,
            'user_to_notify_id' => $vehicle->user->created_by,
            'details' => [
                'message' => "Vehicle {$vehicle->license_plate} تجاوزت السرعة المحددة ({$vehicle->speed} km/h)",
                'vehicle_license_plate' => $vehicle->license_plate,
                'speed' => $vehicle->speed, // ✅ السرعة في details (للعرض)
                'limit' => $speedLimit,
                'notification_reason' => $reason
            ],
            'state_data' => $newState, // ✅ فقط: {'is_exceeding': true/false}
            'last_triggered_at' => now(),
            'is_notified' => false,
        ]);

        Log::info('✅ Speed event created', ['id' => $userEvent->id]);

        $this->sendNotification($userEvent, $vehicle);
    }

    private function sendNotification(UserEvent $userEvent, Vehicle $vehicle)
    {
        $notifyUser = User::find($userEvent->user_to_notify_id);

        if (!$notifyUser) {
            Log::warning("Notify user not found: {$userEvent->user_to_notify_id}");
            return;
        }

        try {
            $notifyUser->notify(new VehicleEventNotification($userEvent->id, $vehicle->id));
            $userEvent->update(['is_notified' => true]);
            Log::info("✓ Notification sent for event {$userEvent->id}");
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
