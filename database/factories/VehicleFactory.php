<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Define common vehicle model names
        $models = ['Sedan', 'SUV', 'Truck', 'Van', 'Hatchback'];

        return [
            // Generate a unique license plate (e.g., 'XYZ-1234')
            'license_plate' => strtoupper($this->faker->unique()->bothify('???-####')),
            // Pick a random model name
            'model_name' => $this->faker->randomElement($models),
        ];
    }
}
