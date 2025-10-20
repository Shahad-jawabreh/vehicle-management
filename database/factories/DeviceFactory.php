<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Define possible device types
        $types = ['Microprocessor', 'Gateway-v1', 'Telematics Unit', 'Data Logger'];

        return [
            'serial_number' => 'SN-' . strtoupper($this->faker->unique()->hexify('########')),
            'device_type' => $this->faker->randomElement($types),
            'is_active' => $this->faker->boolean(90), 
        ];
    }
}
