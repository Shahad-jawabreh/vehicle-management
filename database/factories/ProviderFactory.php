<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider_data = [
            ['name' => 'GPS Receiver', 'type' => 'Sensor', 'key' => 'gps_loc'],
            ['name' => 'OBD-II Interface', 'type' => 'Module', 'key' => 'obd_data'],
            ['name' => 'Temperature Sensor', 'type' => 'Sensor', 'key' => 'temp_degc'],
            ['name' => 'Vibration Monitor', 'type' => 'Sensor', 'key' => 'vib_level'],
            ['name' => 'Fuel Level Sensor', 'type' => 'Sensor', 'key' => 'fuel_pct'],
        ];

        $selected_provider = $this->faker->unique()->randomElement($provider_data);

        return [
            'name' => $selected_provider['name'],
            'type' => $selected_provider['type'],
            'data_key' => $selected_provider['key'] . '_' . $this->faker->unique()->hexify('####'),
            'device_id' => Device::factory(),
        ];
    }
}
