<?php

namespace App\Notifications;

use App\Models\UserEvent;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $userEvent;
    protected $vehicle;

    public function __construct(UserEvent $userEvent, Vehicle $vehicle)
    {
        $this->userEvent = $userEvent;
        $this->vehicle = $vehicle;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventType = $this->userEvent->event->type;
        $details = $this->userEvent->details;

        // Get appropriate emoji and title based on event type
        $config = $this->getEventConfig($eventType);

        $mail = (new MailMessage)
            ->subject("{$config['icon']} Vehicle Alert: {$this->vehicle->license_plate}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("**{$config['title']}**")
            ->line("Vehicle: **{$this->vehicle->license_plate}** ({$this->vehicle->model_name})");

        // Add event-specific details
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
        $eventType = $this->userEvent->event->type;
        $details = $this->userEvent->details;

        return [
            'event_type' => $eventType,
            'vehicle_license' => $this->vehicle->license_plate,
            'vehicle_id' => $this->vehicle->id,
            'message' => $details['message'],
            'details' => $details,
            'user_event_id' => $this->userEvent->id,
            'time' => now()->toDateTimeString(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get event configuration (icon and title)
     */
    private function getEventConfig(string $eventType): array
    {
        $configs = [
            'entered_zone' => [
                'icon' => '✅',
                'title' => 'Vehicle Entered Zone'
            ],
            'left_zone' => [
                'icon' => '🚨',
                'title' => 'Vehicle Left Zone'
            ],
            'speed_exceeded' => [
                'icon' => '⚠️',
                'title' => 'Speed Limit Exceeded'
            ],
            'engine_overheating' => [
                'icon' => '🔥',
                'title' => 'Engine Overheating Alert'
            ],
            'low_fuel' => [
                'icon' => '⛽',
                'title' => 'Low Fuel Warning'
            ],
            'maintenance_due' => [
                'icon' => '🔧',
                'title' => 'Maintenance Required'
            ],
        ];

        return $configs[$eventType] ?? [
            'icon' => '📢',
            'title' => 'Vehicle Alert'
        ];
    }
}
