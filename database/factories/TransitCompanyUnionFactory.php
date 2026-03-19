<?php

namespace Database\Factories;

use App\Models\TransitCompanyUnion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransitCompanyUnion>
 */
class TransitCompanyUnionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
