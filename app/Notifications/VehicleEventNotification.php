<?php

namespace App\Notifications;

use App\Models\UserEvent;
use App\Models\Vehicle;
use App\Models\EventType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class VehicleEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $userEventId;
    public $vehicleId;

    // ✅ تخزين IDs فقط بدلاً من الـ Models
    public function __construct($userEventId, $vehicleId)
    {
        $this->userEventId = $userEventId;
        $this->vehicleId = $vehicleId;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userEvent = UserEvent::with('eventType')->find($this->userEventId);
        $vehicle = Vehicle::find($this->vehicleId);

        if (!$userEvent || !$vehicle) {
            Log::error('❌ UserEvent or Vehicle not found', [
                'user_event_id' => $this->userEventId,
                'vehicle_id' => $this->vehicleId
            ]);
            return (new MailMessage)->line('Error: Data not found');
        }

        if (!$userEvent->eventType) {
            Log::error('❌ EventType is NULL', [
                'user_event_id' => $userEvent->id,
                'event_id' => $userEvent->event_id
            ]);
            return (new MailMessage)->line('Error: Event type not found');
        }

        $eventType = $userEvent->eventType->type;
        $details = $userEvent->details;

        Log::info('🚨 VehicleEventNotification processing', [
            'event_type' => $eventType,
            'vehicle' => $vehicle->license_plate
        ]);

        $config = $this->getEventConfig($eventType);

        $mail = (new MailMessage)
            ->subject("{$config['icon']} Vehicle Alert: {$vehicle->license_plate}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("**{$config['title']}**")
            ->line("Vehicle: **{$vehicle->license_plate}** ({$vehicle->model_name})");

        switch ($eventType) {
            case 'entered_zone':
            case 'left_zone':
                $mail->line("Zone: **{$details['zone_name']}**")
                     ->line("Distance: **{$details['distance']} meters**");
                break;

            case 'speed_exceeded':
                $mail->line("Current Speed: **{$details['speed']} km/h**")
                     ->line("Speed Limit: **{$details['limit']} km/h**");
                break;

            case 'engine_overheating':
                $mail->line("Engine Temperature: **{$details['temperature']}°C**")
                     ->line("Normal Limit: **{$details['limit']}°C**");
                break;
        }

        $mail->line("Message: {$details['message']}")
             ->line("Time: " . now()->format('Y-m-d H:i:s'))
             ->action('View Details', url('/dashboard'))
             ->line('Thank you for using our Vehicle Management System!');

        return $mail;
    }

    public function toDatabase(object $notifiable): array
    {
        $userEvent = UserEvent::with('eventType')->find($this->userEventId);
        $vehicle = Vehicle::find($this->vehicleId);

        if (!$userEvent || !$vehicle) {
            return [];
        }

        $eventType = $userEvent->eventType?->type ?? 'unknown';
        $details = $userEvent->details;

        return [
            'event_type' => $eventType,
            'vehicle_license' => $vehicle->license_plate,
            'vehicle_id' => $vehicle->id,
            'message' => $details['message'],
            'details' => $details,
            'user_event_id' => $userEvent->id,
            'time' => now()->toDateTimeString(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    private function getEventConfig(string $eventType): array
    {
        $configs = [
            'entered_zone' => ['icon' => '✅', 'title' => 'Vehicle Entered Zone'],
            'left_zone' => ['icon' => '🚨', 'title' => 'Vehicle Left Zone'],
            'speed_exceeded' => ['icon' => '⚠️', 'title' => 'Speed Limit Exceeded'],
            'engine_overheating' => ['icon' => '🔥', 'title' => 'Engine Overheating Alert'],
            'low_fuel' => ['icon' => '⛽', 'title' => 'Low Fuel Warning'],
            'maintenance_due' => ['icon' => '🔧', 'title' => 'Maintenance Required'],
        ];

        return $configs[$eventType] ?? ['icon' => '📢', 'title' => 'Vehicle Alert'];
    }
}
