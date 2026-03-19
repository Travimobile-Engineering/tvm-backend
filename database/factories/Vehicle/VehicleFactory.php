<?php

namespace Database\Factories\Vehicle;

use App\Models\TransitCompany;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'company_id' => TransitCompany::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'brand_id' => $this->faker->randomElement([1, 2, 3]),
            'ac' => $this->faker->boolean,
            'plate_no' => $this->faker->unique()->word,
            'color' => $this->faker->colorName,
            'model' => $this->faker->word,
            'air_conditioned' => $this->faker->boolean,
            'seats' => $this->faker->numberBetween(10, 50),
        ];
    }
}
